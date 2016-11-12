<?php

namespace Ems\View;

use Ems\Contracts\View\View as ViewContract;
use Ems\Core\Support\RenderableTrait;
use Ems\Core\Support\ArrayMethods;
use Ems\Contracts\Core\Renderer;

class View implements ViewContract
{
    use RenderableTrait;
    use ArrayMethods;

    /**
     * @var string
     **/
    protected $name = '';

    /**
     * @var string
     **/
    protected $mimeType = 'text/html';

    /**
     * Create a new view.
     *
     * @param string                       $name     (optional)
     * @param \Ems\Contracts\Core\Renderer $renderer (optional)
     **/
    public function __construct($name = '', Renderer $renderer = null)
    {
        $this->name = $name;
        $this->_renderer = $renderer;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function name()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     *
     * @param array|\Traversable|string $name
     * @param mixed                     $value (optional)
     *
     * @return self
     **/
    public function assign($name, $value = null)
    {
        if ($value !== null) {
            $this->_attributes[$name] = $value;

            return $this;
        }

        foreach ($name as $key => $value) {
            $this->_attributes[$key] = $value;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function assignments()
    {
        return $this->_attributes;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function mimeType()
    {
        return $this->mimeType;
    }

    /**
     * Set the mimetype.
     *
     * @param string $mimeType
     *
     * @return self
     **/
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
    }
}
