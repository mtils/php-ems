<?php

namespace Ems\Contracts\Core;

interface Renderer
{
    /**
     * Return true if this renderer can render $item.
     *
     * @param Renderable
     *
     * @return bool
     **/
    public function canRender(Renderable $item);

    /**
     * Renders $item.
     *
     * @param Renderable $item
     *
     * @return string|Stringable
     **/
    public function render(Renderable $item);
}
