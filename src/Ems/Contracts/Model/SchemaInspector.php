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
    public function getStorageUrl(string $class) : Url;

    /**
     * Return the name of $class in storage. In ORM/Database objects
     * this would be the table. In a REST API this would be properties
     * of the name of the endpoint name.
     *
     * @param string $class
     *
     * @return string
     */
    public function getStorageName(string $class) : string;

    /**
     * Return the primary key or multiple keys that build the primary key of this
     * object.
     *
     * @param string $class
     *
     * @return string|string[]
     */
    public function primaryKey(string $class);

    /**
     * Return all keys of $class. This includes relations. The relation
     * has to be delivered by getRelation().
     *
     * @param string $class
     *
     * @return string[]
     */
    public function getKeys(string $class) : array;

    /**
     * Return the relation object that describes the relation to a foreign
     * object. The other object does not have to be in the same storage.
     *
     * @param string $class
     * @param string $name
     *
     * @return Relationship
     */
    public function getRelationship(string $class, string $name) : Relationship;

    /**
     * Return a key=>value array of default values for $class. Created at or
     * generated ids can be implemented here,
     *
     * @param string $class
     * @return array
     */
    public function getDefaults(string $class) : array;

    /**
     * Return a key=>value array of values you want to write on each update.
     * (updated_at would be written here)
     *
     * @param string $class
     * @return array
     */
    public function getAutoUpdates(string $class) : array;

    /**
     * Get the type of $path in $class. This is the same as in Extractor
     *
     * @param string $class
     * @param string $path
     *
     * @return string|null
     */
    public function getType(string $class, string $path) : ?string;
}
