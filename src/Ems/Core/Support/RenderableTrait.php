<?php


namespace Ems\Core\Support;

use Ems\Contracts\Core\Renderer;
use Exception;

/**
 * @see Ems\Contracts\Core\Renderable
 **/
trait RenderableTrait
{

    /**
     * @var \Ems\Contracts\Core\Renderer
     **/
    protected $_renderer;

    /**
     * @var \Ecxeption|null
     **/
    protected $_lastRenderError;

    /**
     * @var callable
     **/
    protected $_errorListener;

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function __toString()
    {


        try {

            if (!$this->_renderer || !$this->_renderer->canRender($this)) {
                return '';
            }

            $output = $this->_renderer->render($this);
            $this->_lastRenderError = null;
            return $output;

        } catch (Exception $e) {
            $this->_lastRenderError = $e;
            return $e->getMessage();
        }

        if ($this->_errorListener) {
            call_user_func($this->_errorListener, $e, $this);
        }

        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @return \Exception|null
     **/
    public function lastRenderError()
    {
        return $this->_lastRenderError;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $handler
     * @return self
     **/
    public function onError(callable $handler)
    {
        $this->_errorListener = $handler;
        return $this;
    }

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
}
