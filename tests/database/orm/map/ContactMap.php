<?php
/**
 *  * Created by mtils on 19.04.20 at 07:00.
 **/

namespace Models\Ems;


use DateTime;
use Ems\Contracts\Model\Relationship;
use Ems\Model\Generator;
use Ems\Model\StaticClassMap;
use Models\Contact;
use Models\User;

class ContactMap extends StaticClassMap
{
    const ID            = 'id';
    const FIRST_NAME    = 'first_name';
    const LAST_NAME     = 'last_name';
    const COMPANY       = 'company';
    const ADDRESS       = 'address';
    const CITY          = 'city';
    const COUNTY        = 'county';
    const POSTAL        = 'postal';
    const PHONE1        = 'phone1';
    const PHONE2        = 'phone2';
    const CREATED_AT    = 'created_at';
    const UPDATED_AT    = 'updated_at';

    const ORM_CLASS = Contact::class;

    const STORAGE_NAME = 'contacts';

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

    public static function user() : Relationship
    {
        return static::relateTo(User::class, UserMap::CONTACT_ID, self::ID);
    }

}