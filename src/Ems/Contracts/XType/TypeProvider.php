<?php


namespace Ems\Contracts\XType;

interface TypeProvider
{
    /**
     * Returns a xtype object for an object property
     *
     * @param string|object $resource The resource name, class or an object of it
     * @param string $path A key name. Can be dotted like address.street.name
     * @return \Ems\Contracts\Xtype\XType
     **/
    public function keyType($resource, $path);
}
