<?php

namespace Ems\Core;

use Ems\Contracts\Core\Named;

class NamedObject implements Named
{
    /**
     * @var mixed
     **/
    protected $id;

    /**
     * @var string
     **/
    protected $name = '';

    /**
     * @param mixed  $id   (optional)
     * @param string $name (optional)
     **/
    public function __construct($id = null, $name = '')
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed (int|string)
     *
     * @see \Ems\Contracts\Core\Identifiable
     **/
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     *
     * @see \Ems\Contracts\Core\Named
     **/
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the id.
     *
     * @param mixed $id
     *
     * @return self
     **/
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set the name.
     *
     * @param string $name
     *
     * @return self
     **/
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
