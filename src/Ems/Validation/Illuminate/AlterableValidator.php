<?php

namespace Ems\Validation\Illuminate;

abstract class AlterableValidator extends Validator
{
    public function setOrmClass(string $ormClass) : AlterableValidator
    {
        $this->ormClass = $ormClass;
        return $this;
    }
}
