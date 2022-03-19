<?php

namespace Ems\Validation\Illuminate;

use Ems\Contracts\Validation\GenericValidator as GenericValidatorContract;
use Ems\Validation\RuleSettingSupport;


class GenericValidator extends Validator implements GenericValidatorContract
{
    use RuleSettingSupport;

    /**
     * @param string $class
     * @return $this
     */
    public function setOrmClass(string $class) : GenericValidator
    {
        $this->ormClass = $class;
        return $this;
    }
}
