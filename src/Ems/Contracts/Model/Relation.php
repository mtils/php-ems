<?php
/**
 *  * Created by mtils on 14.12.17 at 05:31.
 **/

namespace Ems\Contracts\Model;


interface Relation
{
    /**
     * Return the orm object this relation belongs to. This is just mostly an
     * empty object of the class.
     *
     * @return OrmObject
     */
    public function getParent();

    /**
     * Return the key of the parent this relation belongs to. This is not the
     * foreign key.
     *
     * @return string
     */
    public function getParentKey();

    /**
     * Return the related object.
     *
     * @return OrmObject
     */
    public function getRelatedObject();

    /**
     * Return true if the relation could have many related objects.
     *
     * @return bool
     */
    public function hasMany();

    /**
     * Return true if the related object can hold multiple of the parent object.
     *
     * @return bool
     */
    public function belongsToMany();

    /**
     * Return if a minimum of 1 related objects are required when storing the
     * parent. (Minimum cardinality 1 or 0)
     *
     * @return bool
     */
    public function isRequired();

    /**
     * The counterpart of isRequired(): Return if a minimum of 1 parent is
     * required to create a related object.
     *
     * @return bool
     */
    public function isParentRequired();

}