<?php

namespace Ems\XType\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ems\Model\Eloquent\EntityTrait;
use Ems\Contracts\Core\Entity;



class PlainUser extends EloquentModel
{
//     use XTypeTrait;

    public $timestamps = true;
}

class StandardUser extends EloquentModel
{
    use SoftDeletes;
    use XTypeTrait;

    public $timestamps = true;

    protected $visible = ['login', 'email'];

    protected $hidden = ['password'];

    protected $fillable = ['login','email', 'first_name', 'last_name'];

    protected $guarded = ['category_id'];

    protected $dates = ['activated_at'];

    protected $casts = [
        'category_id' => 'int',
        'is_banned'   => 'bool',
        'weight'      => 'float',
        'permissions' => 'array',
        'acl'         => 'object',
        'last_login'  => 'datetime',
        'paw_patrol'  => 'collection',
        'mother'      => 'string'
    ];
}

abstract class BaseModel extends EloquentModel implements Entity
{
    use XTypeTrait;
    use EntityTrait;

    public static $bootCounters = [];

    protected function bootXTypeConfig($config)
    {
        $class = get_class($this);
        if (!isset(static::$bootCounters[$class])) {
            static::$bootCounters[$class] = 0;
        }
        static::$bootCounters[$class]++;
        return $config;
    }

    public function getBootCount()
    {
        $class = get_class($this);
        return isset(static::$bootCounters[$class]) ? static::$bootCounters[$class] : 0;
    }
}

class AdditionalEmails extends BaseModel
{
    protected $xType = [
        'emails' => 'sequence|itemType:[string|min:10|max:128]'
    ];
}

class AdditionalPhoneNumbers extends BaseModel
{
    protected $xType = [
        'phone_numbers' => 'sequence|max:10'
    ];
}

class User extends BaseModel
{
    protected $xType = [
        'login'     => 'string|min:5|max:128',
        'email'     => 'string|min:10|max:255',
        'password'  => 'string|min:6|max:255|required',
        'category'  => 'relation',
        'address'   => 'relation',
        'orders'    => 'relation',
        'tags'      => 'relation',
        'note'      => 'relation'
    ];

    protected $hidden = ['password'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function note()
    {
        return $this->morphOne(Note::class, 'foreign');
    }

}

class ExtendedUser extends User
{
    use SoftDeletes;
    public $timestamps = true;
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->xType['activated'] = 'bool';
        $this->xType['login_count'] = 'number|native_type:int';
        $this->xType['nickname'] = 'string|min:3|max:32';
        $this->xType['misc'] = 'array_access';
        $this->xType['birthday'] = 'date';
        $this->xType['height'] = 'length';
        $this->xType['distance_to_work'] = 'distance';
    }
}

class Category extends BaseModel
{
    protected $xType = [
        'external_id' => 'string|min:5|max:255',
        'name'        => 'string|min:10|max:255'
    ];
}

class Address extends BaseModel
{
    protected $xType = [
        'street'    => 'string|min:10|max:255',
        'country'   => 'relation'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function languages()
    {
        return $this->belongsTo(Country::class);
    }
}

class Country extends BaseModel
{
    protected $xType = [
        'name'      => 'string|min:2|max:255',
        'iso_code'  => 'string|min:2|max:2',
        'area'      => 'area|unit:m²',
        'residents' => 'relation'
    ];

    public function residents()
    {
        return $this->hasManyThrough(User::class, Address::class);
    }
}

class Tag extends BaseModel
{
    protected $xType = [
        'name'    => 'string|min:2|max:255'
    ];
}

class Order extends BaseModel
{
    protected $xType = [
        'name'    => 'string|min:2|max:255',
        'comments'=> 'relation',
        'amount'  => 'money'
    ];

    public function comments()
    {
        return $this->morphMany(Comment::class, 'foreign');
    }
}

class Comment extends BaseModel
{
    protected $xType = [
        'comment'       => 'string|min:5|max:255',
        'foreign_id'    => 'string|min:5|max:64',
        'foreign_type'  => 'string|min:5|max:64'
    ];
}

class Note extends BaseModel
{
    protected $xType = [
        'note'          => 'string|min:5|max:64000',
        'foreign_id'    => 'number|min:1|max:64000',
        'foreign_type'  => 'string|min:5|max:64'
    ];
}

class WrongRelationType extends BaseModel
{
    protected $xType = [
        'note'          => 'relation'
    ];

    public function note()
    {
        return 3;
    }
}

class UniqueCountry extends BaseModel
{

    protected $table = 'countries';

    protected $xType = [
        'name'      => 'string|min:2|max:255',
        'iso_code'  => 'string|min:2|max:2|unique',
        'residents' => 'relation'
    ];

    public function residents()
    {
        return $this->hasManyThrough(User::class, Address::class);
    }
}
