<?php


namespace Ems\Core\Support;


use Ems\Contracts\Core\Renderer;
use Ems\Contracts\Core\Renderable;

use Ems\Core\Patterns\TraitOfResponsibility;



class RendererChain implements Renderer
{

    use TraitOfResponsibility;

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Core\Renderable
     * @return bool
     **/
    public function canRender(Renderable $item)
    {
        return (bool)$this->findReturningTrue('canRender', $item);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Core\Renderable $item
     * @return string
     **/
    public function render(Renderable $item)
    {

        if ($renderer = $this->findReturningTrueOrFail('canRender', $item)) {
            return $renderer->render($item);
        }

        return '';

    }
}
