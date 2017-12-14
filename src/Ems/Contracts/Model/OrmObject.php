<?php
/**
 *  * Created by mtils on 14.12.17 at 05:17.
 **/

namespace Ems\Contracts\Model;


use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\Arrayable;
use Ems\Contracts\Core\ChangeTracking;
use Ems\Contracts\Core\HasKeys;
use Ems\Contracts\Core\Identifiable;
use Ems\Contracts\Core\ObjectAccess;

/**
 * Interface OrmObject
 * 
 * An OrmObject is an object used to store resources. It is not to work only
 * with databases. Its just an interface to work with relation, change tracking...
 * 
 * @package Ems\Contracts\Model
 */
interface OrmObject extends HasKeys, Arrayable, ObjectAccess, ChangeTracking, Identifiable, AppliesToResource
{
    /**
     * Check if $key is a relation.
     *
     * @param string $key
     *
     * @return bool
     */
    public function isRelation($key);

    /**
     * Get the content of relation $key
     *
     * @param $key
     *
     * @return self|OrmCollection
     */
    public function getRelated($key);

    /**
     * Check if the related object(s) of relation $key were loaded.
     *
     * @param $key
     *
     * @return bool
     */
    public function relatedLoaded($key);

    /**
     * Get the relation object for $key.
     *
     * @param string $key
     *
     * @return Relation
     */
    public function getRelation($key);
}