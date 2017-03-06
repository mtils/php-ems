<?php

namespace Ems\Contracts\XType;

interface TypeProvider
{
    /**
     * Returns a xtype object for an object property. If path is null return the
     * Xtype for the whole class
     *
     * @param mixed   $resource The resource name, class, an object of it or just some variable
     * @param string  $path     (optional) A key name. Can be dotted like address.street.name
     *
     * @return XType
     **/
    public function xType($root, $path=null);
}
