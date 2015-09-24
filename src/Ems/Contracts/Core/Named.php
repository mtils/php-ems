<?php

namespace Ems\Contracts\Core;

interface Named extends Identifiable
{
    /**
     * Return a name for this object.
     *
     * @return string
     **/
    public function getName();
}
