<?php

namespace Ems\Validation;

use Ems\Contracts\Core\AppliesToResource;

/**
 * Use this trait to support setting of rules. Use this trait
 * with \Ems\Validation\Validator
 *
 * @see \Ems\Contracts\Validation\GenericValidator
 **/
trait RuleSettingSupport
{

    /**
     * @var AppliesToResource
     **/
    protected $_resource;

    /**
     * @var string
     **/
    protected $_resourceName;

    /**
     * @param array $rules
     *
     * @return self
     **/
    public function setRules(array $rules)
    {
        $this->rules = $rules;
        $this->parsedRules = null;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return AppliesToResource|null
     **/
    public function resource()
    {
        return $this->_resource;
    }

    /**
     * Set the resource of this validator
     *
     * @param string|\Ems\Contracts\Core\AppliesToResource $resource
     *
     * @return self
     **/
    public function setResource(AppliesToResource $resource)
    {
        $this->_resource = $resource;
        return $this;
    }
}
