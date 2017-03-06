<?php


namespace Ems\View\Illuminate;

use Ems\Contracts\Core\Renderable;
use Ems\Contracts\Core\Renderer as RendererContract;
use Ems\Contracts\View\View;
use Illuminate\Contracts\View\Factory as ViewFactory;

/**
 * e.g. for usage with HighlightProviders
 */
class Renderer implements RendererContract
{
    /**
     * @var ViewFactory
     **/
    protected $view;

    /**
     * @param ViewFactory
     **/
    public function __construct(ViewFactory $view)
    {
        $this->view = $view;
    }

    /**
     * {@inheritdoc}
     *
     * @param Renderable $item
     *
     * @return bool
     **/
    public function canRender(Renderable $item)
    {
        return ($item instanceof View);
    }

    /**
     * {@inheritdoc}
     *
     * @param Renderable $item
     *
     * @return string
     **/
    public function render(Renderable $item)
    {
        return $this->view->make($item->name(), $item->assignments())->render();
    }
}
