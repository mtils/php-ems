<?php

namespace Ems\View;

use Ems\Contracts\View\View as ViewContract;
use Ems\Core\Support\RenderableTrait;
use Ems\Core\Support\ArrayMethods;
use Ems\Contracts\Core\Renderer;

/**
 * Class PassthroughView
 *
 * Use this view to just pass a string if you need to pass a
 * string.
 *
 * @package Ems\View
 */
class PassthroughView extends View
{
    use RenderableTrait;
    use ArrayMethods;

    /**
     * @var
     */
    protected $content;

    /**
     * @var string
     */
    protected $contentVarName = 'content';

    /**
     * Create a new view.
     *
     * @param string $name     (optional)
     * @param string $content
     **/
    public function __construct($name = '', $content = '')
    {
        parent::__construct($name);
        $this->content = $content;
    }

    /**
     *
     * @return string
     */
    public function toString()
    {
        if (!$this->_renderer) {
            return $this->content;
        }

        if (!$this->_renderer->canRender($this)) {
            return '';
        }

        if (!isset($this->_attributes[$this->contentVarName])) {
            $this->assign($this->contentVarName, $this->content);
        }

        return $this->_renderer->render($this);
    }

}
