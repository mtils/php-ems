<?php

namespace Ems\Core\Storages;


trait SerializeOptions
{
    /**
     * @var array
     **/
    protected $serializeOptions = [];

    /**
     * @var array
     **/
    protected $deserializeOptions = [];

    /**
     * Get the options for serializing
     *
     * @return array
     **/
    public function getSerializeOptions()
    {
        return $this->serializeOptions;
    }

    /**
     * Set options for the serialize process
     *
     * @param array $options
     *
     * @return self
     **/
    public function setSerializeOptions(array $options)
    {
        $this->serializeOptions = $options;
        return $this;
    }

    /**
     * Get the options for deserializing
     *
     * @return array
     **/
    public function getDeserializeOptions()
    {
        return $this->deserializeOptions;
    }

    /**
     * Set options for the deserialize process
     *
     * @param array $options
     *
     * @return self
     **/
    public function setDeserializeOptions(array $options)
    {
        $this->deserializeOptions = $options;
        return $this;
    }

}
