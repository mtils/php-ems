<?php

namespace Ems\Contracts\Core;

interface Identifiable
{
    /**
     * Return the unique identifier for this object.
     *
     * @return mixed (int|string)
     **/
    public function getId();
}
