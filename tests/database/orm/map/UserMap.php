<?php
/**
 *  * Created by mtils on 19.04.20 at 08:43.
 **/

namespace Models\Ems;


use Ems\Model\Relation;
use Ems\Model\StaticClassMap;
use Models\Contact;
use Models\Group;
use Models\Token;
use Models\User;

class UserMap extends StaticClassMap
{
    const ID            = 'id';
    const EMAIL         = 'email';
    const PASSWORD      = 'password';
    const WEB           = 'web';
    const CONTACT_ID    = 'contact_id';
    const CREATED_AT    = 'created_at';
    const UPDATED_AT    = 'updated_at';

    const ORM_CLASS = User::class;
    const STORAGE_NAME = 'users';
    const STORAGE_URL = 'database://default';

    public static function contact() : Relation
    {
        return static::newRelation()
            ->setRelatedObject(Contact::class)
            ->setHasMany(false)
            ->setRequired(false)
            ->setParentKey(static::CONTACT_ID)
            ->setParentRequired(false);
    }

    public static function tokens() : Relation
    {
        return static::newRelation()
            ->setRelatedObject(Token::class)
            ->setHasMany(true)
            ->setRequired(false)
            ->setParentRequired(true);
    }

    public static function groups() : Relation
    {
        return static::newRelation()
            ->setRelatedObject(Group::class)
            ->setHasMany(true)
            ->setRequired(false)
            ->setParentKey(static::ID)
            ->setParentRequired(false)
            ->setBelongsToMany(true);
    }
}