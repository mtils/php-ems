<?php

namespace Ems\Assets\Laravel;

use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory;
use Ems\Contracts\Assets\Manager;

class AssetsBladeDirectives
{
    /**
     * @var \Ems\Contracts\Assets\Manager
     **/
    protected $manager;

    protected $importDirective = 'asset';

    protected $renderDirective = 'assets';

    protected $assetNamespaceVariable = 'assetNamespace';

    protected $pathToPrefix = [];

    protected $managerByPath = [];

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Let this class inject a groupPrefix into every rendered template
     * which lies in directory $directory. For performance reasons only an
     * absolute match (strpos === 0) will lead to a injection.
     *
     * @param string $groupPrefix
     **/
    public function mapDirectoryToGroupPrefix($directory, $groupPrefix)
    {
        $this->pathToPrefix[$directory] = $groupPrefix;

        return $this;
    }

    /**
     * Return a manager with a group prefix for $viewPath.
     *
     * @param string $viewPath
     *
     * @return \Ems\Contracts\Assets\Manager
     **/
    public function manager($viewPath)
    {
        if (isset($this->managerByPath[$viewPath])) {
            return $this->managerByPath[$viewPath];
        }
        $this->managerByPath[$viewPath] = $this->createManagerForViewPath($viewPath);

        return $this->managerByPath[$viewPath];
    }

    /**
     * Get the prefix for path $viewPath.
     *
     * @param string
     *
     * @return string
     **/
    public function groupPrefix($viewPath)
    {
        foreach ($this->pathToPrefix as $directory => $groupPrefix) {
            if (strpos($viewPath, $directory) === 0) {
                return $groupPrefix;
            }
        }

        return '';
    }

    public static function injectOriginalViewData(Factory $factory)
    {
        $factory->composer('*', function (View $view) {

            $view->with('currentViewName', $view->name());

            if (!method_exists($view, 'getPath')) {
                $view->with('currentViewPath', '');

                return;
            }

            $view->with('currentViewPath', $view->getPath());

        });
    }

    /**
     * Created a manager for $viewPath. If no groupPrefix found return
     * the root manager ($this->manager).
     *
     * @param string $viewPath
     *
     * @return \Ems\Contracts\Assets\Manager
     **/
    protected function createManagerForViewPath($viewPath)
    {
        if ($groupPrefix = $this->groupPrefix($viewPath)) {
            return $this->manager->replicate(['groupPrefix' => $groupPrefix]);
        }

        return $this->manager;
    }

    /**
     * @param \Illuminate\View\Compilers\BladeCompiler $blade
     **/
    public function registerDirectives(BladeCompiler $blade)
    {
        $this->registerImportDirective($blade);
        $this->registerRenderDirective($blade);
    }

    /**
     * @param \Illuminate\View\Compilers\BladeCompiler $blade
     **/
    protected function registerImportDirective(BladeCompiler $blade)
    {
        $class = get_class($this);
        $blade->directive($this->importDirective, function ($expression) use ($class) {
            return "<?php App::make('$class')->manager(\$currentViewPath)->import$expression ?>";
        });
    }

    /**
     * @param \Illuminate\View\Compilers\BladeCompiler $blade
     **/
    protected function registerRenderDirective(BladeCompiler $blade)
    {
        $class = get_class($this);
        $blade->directive($this->renderDirective, function ($expression) use ($class) {
            return "<?php echo App::make('$class')->manager(\$currentViewPath)->render$expression ?>";

        });
    }
}
