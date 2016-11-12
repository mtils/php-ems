<?php

namespace Ems\Contracts\Core;

interface Renderer
{
    /**
     * Return true if this renderer can render $item.
     *
     * @param \Ems\Contracts\Core\Renderable
     *
     * @return bool
     **/
    public function canRender(Renderable $item);

    /**
     * Renders $item.
     *
     * @param \Ems\Contracts\Core\Renderable $item
     *
     * @return string
     **/
    public function render(Renderable $item);
}
