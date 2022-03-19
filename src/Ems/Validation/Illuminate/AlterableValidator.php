<?php

namespace Ems\Validation\Illuminate;

use Ems\Validation\RuleMergingSupport;
use Ems\Contracts\Validation\AlterableValidator as AlterableValidatorContract;

abstract class AlterableValidator extends Validator implements AlterableValidatorContract
{
    use RuleMergingSupport;
    public function setOrmClass(string $ormClass) : AlterableValidatorContract
    {
        $this->ormClass = $ormClass;
        return $this;
    }
}
