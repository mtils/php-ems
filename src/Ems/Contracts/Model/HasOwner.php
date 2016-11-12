<?php


namespace Ems\Contracts\Model;

use Ems\Contracts\Core\Identifiable;

interface HasOwner extends Identifiable
{
    /**
     * Return the owner (user) of this object.
     *
     * @return \Ems\Contracts\Core\Identifiable
     **/
    public function getOwner();
}
