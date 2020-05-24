<?php
/**
 *  * Created by mtils on 24.05.20 at 07:29.
 **/

namespace Ems\Contracts\Model;

use function is_object;

/**
 * Class Relationship
 *
 * This is a value object to store all information about a relationship.
 * It is based on relational model (algebra) not relational database theory in particular.
 * Due to the "not-database-restricted" nature of ems model this terminology is
 * used.
 *
 * @package Ems\Contracts\Model
 *
 * @property string         name The name of the relation. Typically the name of the property in its parent
 * @property object         owner The object this relation belongs to
 * @property object         related The "other" object
 * @property bool           hasMany Can ->owner have multiple ->related?
 * @property bool           belongsToMany Can ->related have multiple ->owner?
 * @property bool           isRequired    Do we need a ->related to store >owner?
 * @property bool           relatedRequiresOwner Do ->related needs an ->owner to get stored?
 * @property string         ownerKey The key in ->owner that points to ->relatedKey or ->junctionOwnerKey
 * @property string         relatedKey The key in ->related that points to ->ownerKey or junctionRelatedKey
 * @property string|object  junction The pivot object or table between ->owner and ->related
 * @property string         junctionOwnerKey The pivot key pointing to ->ownerKey
 * @property string         junctionRelatedKey The pivot key pointing to ->ownerKey
 */
class Relationship
{
    /**
     * @var string
     */
    private $name = '';

    /**
     * @var object
     */
    private $owner;

    /**
     * @var object
     */
    private $related;

    /**
     * @var string
     */
    private $relatedName = '';

    /**
     * @var bool
     */
    private $hasMany = false;

    /**
     * @var bool
     */
    private $belongsToMany = false;

    /**
     * @var bool
     */
    private $isRequired = false;

    /**
     * @var bool
     */
    private $relatedRequiresOwner = false;

    /**
     * @var string
     */
    private $ownerKey = '';

    /**
     * @var string
     */
    private $relatedKey = '';

    /**
     * @var object|string
     */
    private $junction;

    /**
     * @var string
     */
    private $junctionOwnerKey = '';

    /**
     * @var string
     */
    private $junctionRelatedKey = '';

    /**
     * Set the name of this relationship. This is its "property" name in its
     * owner.
     *
     * @param string $name
     *
     * @return $this
     */
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the parent object this relationship belongs to.
     *
     * @param object|string $owner
     * @param string        $ownerKey (optional)
     *
     * @return $this
     */
    public function owner($owner, string $ownerKey='')
    {
        $this->owner = is_object($owner) ? $owner : new $owner();
        if ($ownerKey) {
            $this->ownerKey = $ownerKey;
        }
        return $this;
    }

    /**
     * Set the "other" object which is related to the owner of this
     * relation. Optionally pass the owner key and related key.
     *
     * @param string|object $related
     * @param string        $relatedKey (optional)
     * @param string        $ownerKey (optional)
     *
     * @return $this
     */
    public function relateTo($related, $relatedKey = '', $ownerKey = '')
    {
        $this->related = is_object($related) ? $related : new $related();
        if ($relatedKey) {
            $this->relatedKey = $relatedKey;
        }
        if ($ownerKey) {
            $this->ownerKey = $ownerKey;
        }
        return $this;
    }

    /**
     * The name of this relation in ->related object. This helps avoiding double
     * assignments. This was the query/schema builders can calculate the other
     * direction by themselves.
     *
     * @param string $name
     *
     * @return $this
     */
    public function nameInRelated($name)
    {
        $this->relatedName = $name;
        return $this;
    }

    /**
     * Determine if the ->owner can have multiple ->related.
     *
     * @param bool $hasMany
     *
     * @return $this
     */
    public function hasMany($hasMany=true)
    {
        $this->hasMany = $hasMany;
        return $this;
    }

    /**
     * Determine than ->related can have multiple ->owner
     *
     * @param bool $belongs
     *
     * @return $this
     */
    public function belongsToMany($belongs=true)
    {
        $this->belongsToMany = $belongs;
        return $this;
    }

    /**
     * Set that you have to have at least one ->related before you can store a
     * ->owner. In database theory this means "minimum cardinality".
     *
     * @param bool $required
     *
     * @return $this
     */
    public function makeRequired($required=true)
    {
        $this->isRequired = $required;
        return $this;
    }

    /**
     * The other side of makeRequired(). Does ->related requires at minimum one
     * ->owner to get stored? ("minimum cardinality)
     *
     * @param bool $required
     * @return $this
     */
    public function makeOwnerRequiredForRelated($required=true)
    {
        $this->relatedRequiresOwner = $required;
        return $this;
    }

    /**
     * Add a junction (table or object) to associate ->owner to ->related.
     * In database this is a m:n (or pivot) table.
     * If you pass a string it is assumed you man a "table" or in ems style a
     * "storageName". If you pass an object it is assumed you mean an Object and
     * the name of the table will be determined by
     * storageName($relationship->junction).
     *
     * @param string|object $objectOrTable
     * @param string        $junctionOwnerKey (optional)
     * @param string        $junctionRelatedKey (optional)
     *
     * @return $this
     */
    public function junction($objectOrTable, $junctionOwnerKey='', $junctionRelatedKey='')
    {
        $this->junction = $objectOrTable;
        if ($junctionOwnerKey) {
            $this->junctionOwnerKey = $junctionOwnerKey;
        }
        if ($junctionRelatedKey) {
            $this->junctionRelatedKey = $junctionRelatedKey;
        }
        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'name':
                return $this->name;
            case 'owner':
                return $this->owner;
            case 'related':
                return $this->related;
            case 'relatedName':
                return $this->relatedName;
            case 'hasMany':
                return $this->hasMany;
            case 'belongsToMany':
                return $this->belongsToMany;
            case 'isRequired':
                return $this->isRequired;
            case 'relatedRequiresOwner':
                return $this->relatedRequiresOwner;
            case 'ownerKey':
                return $this->ownerKey;
            case 'relatedKey':
                return $this->relatedKey;
            case 'junction':
                return $this->junction;
            case 'junctionOwnerKey':
                return $this->junctionOwnerKey;
            case 'junctionRelatedKey':
                return $this->junctionRelatedKey;
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'name':
                $this->name($name);
                return;
            case 'owner':
                $this->owner($value);
                return;
            case 'related':
                $this->relateTo($value);
                return;
            case 'hasMany':
                $this->hasMany($value);
                return;
            case 'belongsToMany':
                $this->belongsToMany($value);
                return;
            case 'isRequired':
                $this->makeRequired($value);
                return;
            case 'relatedRequiresOwner':
                $this->makeOwnerRequiredForRelated($value);
                return;
            case 'relatedName':
                $this->nameInRelated($value);
                return;
            case 'ownerKey':
                $this->owner($this->owner, $value);
                return;
            case 'relatedKey':
                $this->relateTo($this->related, $value);
                return;
            case 'junction':
                $this->junction($value);
                return;
            case 'junctionOwnerKey':
                $this->junction($this->junction, $value);
                return;
            case 'junctionRelatedKey':
                $this->junction($this->junction, '', $value);
                return;
        }
    }
}