<?php
/**
 *  * Created by mtils on 14.12.17 at 16:53.
 **/

namespace Ems\Model;


use Ems\Contracts\Model\OrmCollection;
use Ems\Contracts\Model\OrmObject as OrmObjectContract;

class GenericOrmCollection extends GenericPaginatableResult implements OrmCollection
{
    /**
     * @var OrmObjectContract
     */
    protected $parent;

    /**
     * @var string
     */
    protected $parentKey;

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