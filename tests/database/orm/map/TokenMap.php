<?php

/**
 *  * Created by mtils on 19.04.20 at 08:20.
 **/

namespace Models\Ems;

use DateTime;
use Ems\Contracts\Model\Relationship;
use Ems\Model\Generator;
use Ems\Model\StaticClassMap;
use Models\Token;
use Models\User;

class TokenMap extends StaticClassMap
{
    const ID            = 'id';
    const USER_ID       = 'user_id';
    const TOKEN_TYPE    = 'token_type';
    const TOKEN         = 'token';
    const EXPIRES_AT    = 'expires_at';
    const CREATED_AT    = 'created_at';
    const UPDATED_AT    = 'updated_at';

    const ORM_CLASS = Token::class;
    const STORAGE_NAME = 'tokens';
    const STORAGE_URL = 'database://default';

    protected $types = [
        self::ID         => 'int',
        self::CREATED_AT => DateTime::class,
        self::UPDATED_AT => DateTime::class
    ];

    protected $defaults = [
        self::CREATED_AT => Generator::NOW,
        self::UPDATED_AT => Generator::NOW
    ];

    protected $autoUpdates = [
        self::UPDATED_AT => Generator::NOW
    ];

    // Should not be needed in future
    public static function user() : Relationship
    {
        return static::relateTo(User::class, 'id', 'user_id')
            ->required(true);
        return static::newRelation()
            ->setRelatedObject(User::class)
            ->setHasMany(false)
            ->setRequired(true)
            ->setParentKey(static::USER_ID)
            ->setParentRequired(false);
    }
}