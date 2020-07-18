<?php

/**
 *  * Created by mtils on 29.03.20 at 13:54.
 **/

namespace Ems\Contracts\Model;

use Ems\Contracts\Core\Url;

interface SchemaInspector
{

    /**
     * Return the storage url.
     *
     * @param string $class
     *
     * @return Url
     */
    public function getStorageUrl($class);

    /**
     * Return the name of $class in storage. In ORM/Database objects
     * this would be the table. In a REST API this would be properties
     * of the name of the endpoint name.
     *
     * @param string $class
     *
     * @return string
     */
    public function getStorageName($class);

    /**
     * Return the primary key or multiple keys that build the primary key of this
     * object.
     *
     * @param string $class
     *
     * @return string|string[]
     */
    public function primaryKey($class);

    /**
     * Return all keys of $class. This includes relations. The relation
     * has to be delivered by getRelation().
     *
     * @param string $class
     *
     * @return string[]
     */
    public function getKeys($class);

    /**
     * Return the relation obeject that describes the relation to a foreign
     * object. The other object does not have to be in the same storage.
     *
     * @param string $class
     * @param string $name
     *
     * @return Relationship
     */
    public function getRelationship($class, $name);
}
