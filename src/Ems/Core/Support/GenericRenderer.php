<?php

/**
 *  * Created by mtils on 28.03.20 at 07:51.
 **/

namespace Ems\Core\Support;

use Ems\Contracts\Core\Renderable;
use Ems\Contracts\Core\Renderer;
use Ems\Contracts\Core\Stringable;

use function call_user_func;

/**
 * Class GenericRenderer
 *
 * This is a helper to create a renderer on the fly.
 *
 * @package Ems\Core\Support
 */
class GenericRenderer implements Renderer
{
    /**
     * @var callable
     */
    private $canRenderCallable;

    /**
     * @var callable
     */
    private $renderCallable;

    /**
     * GenericRenderer constructor.
     *
     * @param callable $renderCallable
     * @param callable $canRenderCallable (optional)
     */
    public function __construct(callable $renderCallable, callable $canRenderCallable = null)
    {
        $this->renderCallable = $renderCallable;
        $this->canRenderCallable = $canRenderCallable ?: function () {
            return true;
        };
    }

    /**
     * {@inheritDoc}
     *
     * @param Renderable
     *
     * @return bool
     **/
    public function canRender(Renderable $item)
    {
        return call_user_func($this->canRenderCallable, $item);
    }

    /**
     * {@inheritDoc}
     *
     * @param Renderable $item
     *
     * @return string|Stringable
     **/
    public function render(Renderable $item)
    {
        return call_user_func($this->renderCallable, $item);
    }

}