<?php

/**
 *  * Created by mtils on 19.04.20 at 08:17.
 **/

namespace Models\Ems;

use Ems\Contracts\Model\Relationship;
use Ems\Model\Relation;
use Ems\Model\StaticClassMap;
use Models\Group;
use Models\User;

class GroupMap extends StaticClassMap
{
    const ID            = 'id';
    const NAME          = 'name';
    const CREATED_AT    = 'created_at';
    const UPDATED_AT    = 'updated_at';

    const ORM_CLASS = Group::class;
    const STORAGE_NAME = 'groups';
    const STORAGE_URL = 'database://default';

    // Should not bee needed in future
    public static function users() : Relationship
    {
        return static::relateTo(User::class, 'id', 'id')
            ->hasMany(true)
            ->belongsToMany(true)
            ->makeOwnerRequiredForRelated(true)
            ->junction('user_group', 'user_id', 'group_id');

        return static::newRelation()
            ->setParent(static::ORM_CLASS)
            ->setRelatedObject(User::class)
            ->setHasMany(true)
            ->setRequired(false)
            ->setParentKey(static::ID)
            ->setParentRequired(false)
            ->setBelongsToMany(true);
    }
}