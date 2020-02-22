<?php

namespace Ems\Contracts\Core;

interface Renderable extends Stringable
{
    /**
     * Returns the mimetype of this renderable.
     *
     * @return string
     **/
    public function mimeType();

    /**
     * Return the assigned Renderer.
     *
     * @return Renderer
     **/
    public function getRenderer();

    /**
     * Set the renderer to render this renderable.
     *
     * @param Renderer $renderer
     *
     * @return self
     **/
    public function setRenderer(Renderer $renderer);
}
