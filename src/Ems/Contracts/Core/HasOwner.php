<?php 

namespace Ems\Contracts\Core;

interface HasOwner extends Identifiable
{

    /**
     * Return the owner (user) of this object
     *
     * @return \Ems\Contracts\Core\Identifiable
     **/
    public function getOwner();

}
