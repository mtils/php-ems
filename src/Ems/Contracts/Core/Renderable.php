<?php


namespace Ems\Contracts\Core;


interface Renderable
{

    /**
     * Returns the mimetype of this renderable
     *
     * @return string
     **/
    public function mimeType();

    /**
     * Renders this object (through its renderer)
     *
     * @return string
     **/
    public function __toString();

    /**
     * Since __toString doesnt allow to throw exceptions
     * get the last Exception by this method
     *
     * @return \Exception|null
     **/
    public function lastRenderError();

    /**
     * When an error occures call this handler
     * The exception and this object will be passed
     *
     * @param callable $handler
     * @return self
     **/
    public function onError(callable $handler);

    /**
     * Return the assigned Renderer
     *
     * @return \Ems\Contracts\Core\Renderer
     **/
    public function getRenderer();

    /**
     * Set the renderer to render this renderable
     *
     * @param \Ems\Contracts\Core\Renderer $renderer
     * @return self
     **/
    public function setRenderer(Renderer $renderer);
}
