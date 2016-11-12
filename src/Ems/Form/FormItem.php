<?php

namespace Ems\Form;

class FormItem
{
    /**
     * @var string
     **/
    protected $id;

    /**
     * @var string
     **/
    protected $name;

    /**
     * The Tooltip (title attribute).
     *
     * @var string
     */
    protected $tooltip;

    /**
     * The typical label of a field, not its html title.
     *
     * @var string
     **/
    protected $title;

    /**
     * @var string
     **/
    protected $description;

    /**
     * @var \Ems\Core\Collections\StringList
     */
    protected $cssClasses;

    /**
     * (HTML) Attributes.
     *
     * @var Attributes
     */
    protected $attributes = null;

    /**
     * Classname for Templates.
     *
     * @var string
     */
    protected $className = '';
}
