<?php

namespace Ems\Validation\Illuminate;


class GenericValidator extends Validator
{

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
