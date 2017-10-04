<?php

namespace Ems\Validation;

/**
 * Use this trait to support merging of rules. Use this trait
 * with \Ems\Validation\Validator
 *
 * @see \Ems\Contracts\Validation\AlterableValidator
 **/
trait RuleMergingSupport
{

    /**
     * @var array
     **/
    protected $extendedRules = [];

    /**
     * @param array $rules
     *
     * @return self
     **/
    public function mergeRules(array $rules)
    {
        $this->extendedRules = array_merge($this->extendedRules, $rules);
        $this->parsedRules = null;
        return $this;
    }

    /**
     * This method is called just to have a hook to build initial rules.
     *
     * @return array
     **/
    protected function buildRules()
    {
        $rules = array_merge(parent::buildRules(), $this->extendedRules);
        return $rules;
    }
}
