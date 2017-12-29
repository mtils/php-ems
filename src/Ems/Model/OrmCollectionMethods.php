<?php
/**
 *  * Created by mtils on 28.12.17 at 06:41.
 **/

namespace Ems\Model;


use Ems\Contracts\Model\OrmObject as OrmObjectContract;

trait OrmCollectionMethods
{
    /**
     * @var OrmObjectContract
     */
    protected $ormObject;

    /**
     * @var OrmObjectContract
     */
    protected $parent;

    /**
     * @var string
     */
    protected $parentKey;

    /**
     * @return OrmObjectContract
     */
    public function ormObject()
    {
        return $this->ormObject;
    }

    /**
     * @param OrmObjectContract $ormObject
     *
     * @return $this
     */
    public function setOrmObject(OrmObjectContract $ormObject)
    {
        $this->ormObject = $ormObject;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return OrmObjectContract
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set the parent this collection belongs to.
     * If there is no parent, just assign nothing.
     *
     * @param OrmObjectContract $parent
     *
     * @return $this
     */
    public function setParent(OrmObjectContract $parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getParentKey()
    {
        return $this->parentKey;
    }

    /**
     * Set the relation of this collection (if it is one).
     *
     * @param string $parentKey
     *
     * @return $this
     */
    public function setParentKey($parentKey)
    {
        $this->parentKey = $parentKey;
        return $this;
    }
}