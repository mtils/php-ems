<?php
/**
 *  * Created by mtils on 28.11.2021 at 22:20.
 **/

namespace Ems\View;

use Ems\Contracts\Core\Renderable;
use Ems\Contracts\Core\Renderer;
use Ems\Contracts\View\View as ViewContract;
use Ems\Core\Exceptions\UnsupportedParameterException;

use function extract;

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

        ob_start();
        extract($vars);

        include($this->fileFinder->file($item->name()));

        return (string)ob_get_clean();
    }



}