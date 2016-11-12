<?php

namespace Ems\Core\Support;

use Ems\Contracts\Core\Renderer;
use Exception;

/**
 * @see Ems\Contracts\Core\Stringable
 **/
trait StringableTrait
{
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
        return $this->tryToRender();
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
     *
     * @return self
     **/
    public function onError(callable $handler)
    {
        $this->_errorListener = $handler;

        return $this;
    }

    /**
     * Just a little hook which gets called before the renderer is called.
     **/
    protected function prepareForToString()
    {
    }

    /**
     * Try to render the string an call the handler.
     *
     * @return string
     **/
    protected function tryToRender()
    {
        try {
            $this->prepareForToString();

            $output = $this->renderString();
            $this->_lastRenderError = null;

            return $output;
        } catch (Exception $e) {
            return $this->processException($e);
        }
    }

    /**
     * Renders the result. Is just inside its own method to allow easy
     * overwriting __toString().
     *
     * @return string
     **/
    protected function renderString()
    {
        return '';
    }

    /**
     * Process the exception which did occur during toString.
     *
     * @param \Exception
     *
     * @return string
     **/
    protected function processException(Exception $e)
    {
        $this->_lastRenderError = $e;

        if ($this->_errorListener) {
            call_user_func($this->_errorListener, $e, $this);
        }

        return '';
    }
}
