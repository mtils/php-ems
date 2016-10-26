<?php


namespace Ems\Core\Support;

use Ems\Contracts\Core\Renderer;
use Exception;

/**
 * @see Ems\Contracts\Core\Renderable
 **/
trait RenderableTrait
{

    use StringableTrait;

    /**
     * @var \Ems\Contracts\Core\Renderer
     **/
    protected $_renderer;

    /**
     * {@inheritdoc}
     *
     * @return \Ems\Contracts\Core\Renderer
     **/
    public function getRenderer()
    {
        return $this->_renderer;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Core\Renderer $renderer
     * @return self
     **/
    public function setRenderer(Renderer $renderer)
    {
        $this->_renderer = $renderer;
        return $this;
    }

    /**
     * Renders the result. Is just inside its own method to allow easy
     * overwriding __toString()
     *
     * @return string
     **/
    protected function renderString()
    {
        if (!$this->_renderer || !$this->_renderer->canRender($this)) {
            return '';
        }

        return $this->_renderer->render($this);

    }

}
