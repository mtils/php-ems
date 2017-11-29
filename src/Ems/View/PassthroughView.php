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
     * Create a new view.
     *
     * @param string $name     (optional)
     * @param string $content
     **/
    public function __construct($name = '', $content = '')
    {
        $this->name = $name;
        $this->content = $content;
    }

}
