<?php
/**
 *  * Created by mtils on 14.12.17 at 05:45.
 **/

namespace Ems\Model;

use Ems\Contracts\Model\OrmObject as OrmObjectContract;
use Ems\Contracts\Model\Relation as RelationContract;

class Relation implements RelationContract
{
    /**
     * @var OrmObjectContract
     */
    protected $parent;

    /**
     * @var string
     */
    protected $parentKey;

    /**
     * @var OrmObjectContract
     */
    protected $relatedObject;

    /**
     * @var bool
     */
    protected $hasMany = false;

    /**
     * @var bool
     */
    protected $belongsToMany = false;

    /**
     * @var bool
     */
    protected $required = false;

    /**
     * @var bool
     */
    protected $parentRequired = false;

    /**
     * @inheritdoc
     *
     * @return OrmObjectContract
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getParentKey()
    {
        return $this->parentKey;
    }

    /**
     * @inheritdoc
     *
     * @return object
     */
    public function getRelatedObject()
    {
        return $this->relatedObject;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function hasMany()
    {
        return $this->hasMany;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function belongsToMany()
    {
        return $this->belongsToMany;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function isParentRequired()
    {
        return $this->parentRequired;
    }

    /**
     * @param OrmObjectContract $parent
     *
     * @return $this
     */
    public function setParent(OrmObjectContract $parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @param string $parentKey
     *
     * @return $this
     */
    public function setParentKey($parentKey)
    {
        $this->parentKey = $parentKey;
        return $this;
    }

    /**
     * @param object $relatedObject
     *
     * @return $this
     */
    public function setRelatedObject($relatedObject)
    {
        $this->relatedObject = $relatedObject;
        return $this;
    }

    /**
     * @param bool $hasMany
     *
     * @return $this
     */
    public function setHasMany($hasMany)
    {
        $this->hasMany = $hasMany;
        return $this;
    }

    /**
     * @param bool $belongsToMany
     *
     * @return $this
     */
    public function setBelongsToMany($belongsToMany)
    {
        $this->belongsToMany = $belongsToMany;
        return $this;
    }

    /**
     * @param bool $required
     *
     * @return $this
     */
    public function setRequired($required)
    {
        $this->required = $required;
        return $this;
    }

    /**
     * @param bool $parentRequired
     *
     * @return $this
     */
    public function setParentRequired($parentRequired)
    {
        $this->parentRequired = $parentRequired;
        return $this;
    }
}