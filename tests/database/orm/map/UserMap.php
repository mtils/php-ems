<?php
/**
 *  * Created by mtils on 19.04.20 at 08:43.
 **/

namespace Models\Ems;


use Ems\Contracts\Model\Relationship;
use Ems\Model\Relation;
use Ems\Model\StaticClassMap;
use Models\Contact;
use Models\Group;
use Models\Project;
use Models\Token;
use Models\User;

use const TOKEN_PARSE;

class UserMap extends StaticClassMap
{
    const ID            = 'id';
    const EMAIL         = 'email';
    const PASSWORD      = 'password';
    const WEB           = 'web';
    const CONTACT_ID    = 'contact_id';
    const PARENT_ID     = 'parent_id';
    const CREATED_AT    = 'created_at';
    const UPDATED_AT    = 'updated_at';

    const ORM_CLASS = User::class;
    const STORAGE_NAME = 'users';
    const STORAGE_URL = 'database://default';

    public static function contact() : Relationship
    {
        return static::relateTo(Contact::class, ContactMap::ID, self::CONTACT_ID);
    }

    public static function parent() : Relationship
    {
        return static::relateTo(self::ORM_CLASS, self::ID, self::PARENT_ID);
    }

    public static function tokens() : Relationship
    {
        return static::relateTo(Token::class, TokenMap::USER_ID, self::ID)
            ->hasMany(true)
            ->makeOwnerRequiredForRelated(true);
    }

    public static function groups() : Relationship
    {
        return static::relateTo(Group::class, GroupMap::ID, self::ID)
            ->hasMany(true)
            ->belongsToMany(true)
            ->makeRequired(true)
            ->junction('user_group', 'user_id', 'group_id');
    }

    public static function projects() : Relationship
    {
        return static::relateTo(Project::class, ProjectMap::OWNER_ID, self::ID)
            ->hasMany(true)
            ->makeOwnerRequiredForRelated(true);
    }
}