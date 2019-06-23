<?php
/**
 *  * Created by mtils on 23.06.19 at 08:21.
 **/

namespace Ems\Contracts\Core;


use function call_user_func;
use Exception;

/**
 * @see \Ems\Contracts\Core\Stringable
 **/
trait StringableTrait
{
    /**
     * @var Exception|null
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
     * @return $this
     **/
    public function onError(callable $handler)
    {
        $this->_errorListener = $handler;

        return $this;
    }

    /**
     * Try to render the string an call the handler.
     *
     * @return string
     **/
    protected function tryToRender()
    {
        try {

            $output = $this->toString();
            $this->_lastRenderError = null;

            return $output;
        } catch (Exception $e) {
            return $this->processException($e);
        }
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