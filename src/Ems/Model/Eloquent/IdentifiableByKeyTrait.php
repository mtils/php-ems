<?php 


namespace Ems\Model\Eloquent;


trait IdentifiableByKeyTrait
{

    /**
     * Return the unique identifier for this object.
     *
     * @return mixed (int|string)
     **/
    public function getId()
    {
        return $this->getKey();
    }

}