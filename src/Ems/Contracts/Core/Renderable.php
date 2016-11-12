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
     * @return \Ems\Contracts\Core\Renderer
     **/
    public function getRenderer();

    /**
     * Set the renderer to render this renderable.
     *
     * @param \Ems\Contracts\Core\Renderer $renderer
     *
     * @return self
     **/
    public function setRenderer(Renderer $renderer);
}
