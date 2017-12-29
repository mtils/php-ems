<?php
/**
 *  * Created by mtils on 14.12.17 at 05:25.
 **/

namespace Ems\Contracts\Model;

/**
 * Interface OrmCollection
 *
 * This is an interface for all collections returned by an OrmObject relation or
 * Orm Search.
 *
 * @package Ems\Contracts\Model
 */
interface OrmCollection extends Result
{

    /**
     * Get the OrmObject this collection can contain.
     *
     * @return OrmObject
     */
    public function ormObject();

    /**
     * Get the OrmObject this collection is related to.
     *
     * @return OrmObject
     */
    public function getParent();

    /**
     * Get the relation key this collection is related to.
     *
     * @return string
     */
    public function getParentKey();

}