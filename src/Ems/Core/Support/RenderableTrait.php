<?php

namespace Ems\Core\Support;

use Ems\Contracts\Core\Renderer;
use Ems\Contracts\Core\StringableTrait as StringableMethods;

/**
 * @see \Ems\Contracts\Core\Renderable
 **/
trait RenderableTrait
{
    use StringableMethods;

    /**
     * @var Renderer
     **/
    protected $_renderer;

    /**
     * {@inheritdoc}
     *
     * @return Renderer
     **/
    public function getRenderer()
    {
        return $this->_renderer;
    }

    /**
     * {@inheritdoc}
     *
     * @param Renderer $renderer
     *
     * @return self
     **/
    public function setRenderer(Renderer $renderer)
    {
        $this->_renderer = $renderer;

        return $this;
    }

    /**
     * Renders the result. Is just inside its own method to allow easy
     * overwriting __toString().
     *
     * @return string
     **/
    public function toString()
    {
        if (!$this->_renderer || !$this->_renderer->canRender($this)) {
            return '';
        }

        return $this->_renderer->render($this);
    }
}
