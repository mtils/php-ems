<?php

namespace Ems\Validation\Illuminate;

use Ems\Contracts\Validation\GenericValidator as GenericValidatorContract;
use Ems\Validation\RuleSettingSupport;


class GenericValidator extends Validator implements GenericValidatorContract
{
    use RuleSettingSupport;
}
