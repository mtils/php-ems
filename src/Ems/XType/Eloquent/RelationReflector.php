<?php

namespace Ems\XType\Eloquent;

use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Contracts\Core\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use function method_exists;

class RelationReflector
{
    /**
     * Return an array describing the xtype of a relational property
     *
     * @param Model $model
     * @param string $key
     *
     * @return array
     **/
    public function buildRelationXTypeInfo(Model $model, $key)
    {
        $relation = $model->$key();

        switch (true) {

            case $relation instanceof BelongsTo:
                return $this->buildBelongsToInfo($key, $relation);

            case $relation instanceof BelongsToMany:
                return $this->buildBelongsToManyInfo($key, $relation);

            case $relation instanceof HasMany:
                return $this->buildHasManyInfo($key, $relation);

            case $relation instanceof HasOne:
                return $this->buildHasOneInfo($key, $relation);

            case $relation instanceof HasManyThrough:
                return $this->buildHasManyThroughInfo($key, $relation);

            case $relation instanceof MorphOne:
                return $this->buildMorphOneInfo($key, $relation);

            case $relation instanceof MorphMany:
                return $this->buildMorphManyInfo($key, $relation);

            default:
                $modelClass = get_class($model);
                $relationType = Type::of($relation);

                throw new UnsupportedParameterException("Result of $modelClass->$key() did not return a Relation (it returned $relationType)");
        }
    }

    /**
     * @param string $key
     * @param BelongsTo $relation
     *
     * @return array
     **/
    protected function buildBelongsToInfo($key, BelongsTo $relation)
    {
        $other = $relation->getRelated();
        return [
            'type'         => 'object|class:'.get_class($other),
            'foreign_keys' => [$this->getForeignKey($relation)]
        ];
    }

    /**
     * @param string $key
     * @param HasOne $relation
     *
     * @return array
     **/
    protected function buildHasOneInfo($key, HasOne $relation)
    {
        $other = $relation->getRelated();
        return [
            'type'         => 'object|class:'.get_class($other),
            'foreign_keys' => []
        ];
    }

    /**
     * @param string $key
     * @param HasMany $relation
     *
     * @return array
     **/
    protected function buildHasManyInfo($key, HasMany $relation)
    {
        $other = $relation->getRelated();
        return [
            'type'         => 'sequence|itemType:[object|class:'.get_class($other).']',
            'foreign_keys' => []
        ];
    }

    /**
     * @param string $key
     * @param BelongsToMany $relation
     *
     * @return array
     **/
    protected function buildBelongsToManyInfo($key, BelongsToMany $relation)
    {
        $other = $relation->getRelated();
        return [
            'type'         => 'sequence|itemType:[object|class:'.get_class($other).']',
            'foreign_keys' => [$this->getForeignKey($relation)]
        ];
    }

    /**
     * @param string $key
     * @param HasManyThrough $relation
     *
     * @return array
     **/
    protected function buildHasManyThroughInfo($key, HasManyThrough $relation)
    {
        $other = $relation->getRelated();
        return [
            'type'         => 'sequence|itemType:[object|class:'.get_class($other).']',
            'foreign_keys' => []
        ];
    }

    /**
     * @param string $key
     * @param MorphOne $relation
     *
     * @return array
     **/
    protected function buildMorphOneInfo($key, MorphOne $relation)
    {
        $other = $relation->getRelated();
        return [
            'type'         => 'object|class:'.get_class($other),
            'foreign_keys' => []
        ];
    }

    /**
     * @param string $key
     * @param MorphMany $relation
     *
     * @return array
     **/
    protected function buildMorphManyInfo($key, MorphMany $relation)
    {
        $other = $relation->getRelated();
        return [
            'type'         => 'sequence|itemType:[object|class:'.get_class($other).']',
            'foreign_keys' => []
        ];
    }

    /**
     * @param Relation $relation
     *
     * @return string
     **/
    protected function getForeignKey(Relation $relation)
    {
        // Renamed in Laravel 5.4
        if (method_exists($relation, 'getQualifiedForeignKeyName')) {
            return $relation->getQualifiedForeignKeyName();
        }
        // Renamed in Laravel 5.5
        if (method_exists($relation, 'getQualifiedForeignPivotKeyName')) {
            return $relation->getQualifiedForeignPivotKeyName();
        }
        // Renamed in Laravel 5.6
        if (method_exists($relation, 'getExistenceCompareKey')) {
            return $relation->getExistenceCompareKey();
        }
        return $relation->getForeignKey();
    }
}
