<?php
/**
 *  * Created by mtils on 28.11.2021 at 22:20.
 **/

namespace Ems\View;

use Ems\Contracts\Core\Renderable;
use Ems\Contracts\Core\Renderer;
use Ems\Contracts\View\View as ViewContract;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Exceptions\UnsupportedParameterException;
use LogicException;
use Psr\Container\ContainerInterface;

use function array_merge;
use function array_pop;
use function extract;
use function is_array;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;
use function str_replace;

/**
 * This is a simple renderer for parsing php templates.
 */
class PhpRenderer implements Renderer
{
    /**
     * @var ViewFileFinder
     */
    protected $fileFinder;

    /**
     * @var array
     */
    protected $extendStack = [];

    /**
     * @var array
     */
    protected $sections = [];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $shares = [];

    /**
     * @var string
     */
    protected $parentSectionPlaceholder = '__parent__section__$name__';

    /**
     * @var string
     */
    protected $childPlaceHolder = '__child__';

    /**
     * @var string[]
     */
    protected $currentSectionStack = [];

    /**
     * @param ViewFileFinder $fileFinder
     */
    public function __construct(ViewFileFinder $fileFinder)
    {
        $this->fileFinder = $fileFinder;
    }

    /**
     * @param Renderable $item
     * @return bool
     */
    public function canRender(Renderable $item)
    {
        return $item instanceof ViewContract;
    }

    /**
     * Render the passed item. Just let it be evaluated by php.
     *
     * @param Renderable $item
     * @return string
     */
    public function render(Renderable $item)
    {
        if (!$item instanceof ViewContract) {
            throw new UnsupportedParameterException("PhpRenderer can only render " . ViewContract::class);
        }
        $vars = $item->assignments();

        if ($this->shares) {
            $vars = array_merge($vars, $this->shares);
        }

        ob_start();
        extract($vars);

        $extendCountBefore = count($this->extendStack);

        include($this->fileFinder->file($item->name()));
        $result = (string)ob_get_clean();

        if (count($this->extendStack) <= $extendCountBefore) {
            return $result;
        }

        $outerView = array_pop($this->extendStack);
        $parent = $this->render((new View($outerView))->assign($vars));

        return str_replace($this->childPlaceHolder, $result, $parent);
    }

    /**
     * Render another template "around" the current template.
     *
     * @param string $view
     * @return void
     */
    public function extend(string $view)
    {
        $this->extendStack[] = $view;
    }

    /**
     * @param string $name
     * @param array $variables
     * @return string
     */
    public function partial(string $name, array $variables=[]) : string
    {
        return $this->render((new View($name))->assign($variables));
    }

    /**
     * Start a section.
     *
     * @param string $name
     * @return void
     */
    public function section(string $name)
    {
        echo "\n"; // looks more clean in the output
        $this->currentSectionStack[] = $name;
        $level = ob_get_level();
        ob_start();
        if (isset($this->sections[$name])) {
            $this->sections[$name]['level'] = $level;
            return;
        }
        $this->sections[$name] = [
            'level' => $level,
            'output' => ''
        ];
    }

    /**
     * End a section and return its parsed result.
     *
     * @param string $name
     * @return string
     */
    public function end(string $name) : string
    {
        $result = (string)ob_get_clean();
        if (!isset($this->sections[$name])) {
            throw new LogicException("Ending section $name without ever starting it");
        }
        if (ob_get_level() != $this->sections[$name]['level']) {
            throw new LogicException("Not matching ob level when ending Section $name.");
        }
        $currentSection = array_pop($this->currentSectionStack);
        if ($currentSection != $name) {
            throw new LogicException("Closing section '$name' does not match current section stack $currentSection.");
        }

        if ($this->sections[$name]['output']) {
            $result = str_replace($this->makeParentSectionPlaceHolder($name), $result, $this->sections[$name]['output']);
        }

        $this->sections[$name]['output'] = $result;
        return $result;
    }

    /**
     * Insert the parent section content at this place.
     *
     * @param string $section
     * @return string
     */
    public function parent(string $section='') : string
    {
        return $this->makeParentSectionPlaceHolder($section ?: $this->currentSectionName());
    }

    /**
     * Insert the child template (the one that extends the current) here.
     *
     * @return string
     */
    public function child() : string
    {
        return $this->childPlaceHolder;
    }

    /**
     * Resolve objects by the container.
     *
     * @param string $id
     * @return mixed
     */
    public function get(string $id)
    {
        if ($this->container) {
            return $this->container->get($id);
        }
        throw new UnConfiguredException('IOCContainer was not assigned to the renderer');
    }

    /**
     * Share a variable between all templates.
     *
     * @param string|array $key
     * @param mixed        $value (optional)
     * @return $this
     */
    public function share($key, $value=null) : PhpRenderer
    {
        $values = is_array($key) ? $key : [$key => $value];
        foreach ($values as $key=>$value) {
            $this->shares[$key] = $value;
        }
        return $this;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer() : ContainerInterface
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container) : PhpRenderer
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @return array
     */
    public function getShared(): array
    {
        return $this->shares;
    }

    /**
     * @param string $name
     * @return string
     */
    private function makeParentSectionPlaceHolder(string $name) : string
    {
        return str_replace('$name', $name, $this->parentSectionPlaceholder);
    }

    /**
     * @return string
     */
    protected function currentSectionName() : string
    {
        $count = count($this->currentSectionStack);
        return $count > 0 ? $this->currentSectionStack[$count-1] : '';
    }
}