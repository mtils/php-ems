<?php


namespace Ems\Contracts\Validation;

use Ems\Contracts\Core\Entity;

interface Constraint
{

    public function passes($value, Entity $entity=null);

    public function fails($value, Entity $entity=null);

    public function with();

}
