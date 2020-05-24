<?php

/**
 *  * Created by mtils on 04.04.20 at 13:16.
 **/

namespace Ems\Model\Database;

use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Core\Expression;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Model\Database\Parentheses;
use Ems\Contracts\Model\Database\Predicate;
use Ems\Contracts\Model\Database\Query;
use Ems\Contracts\Model\OrmQuery;
use Ems\Contracts\Model\Relation;
use Ems\Contracts\Model\Relationship;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Core\KeyExpression;

use RuntimeException;

use function array_keys;
use function array_unique;
use function class_exists;
use function get_class;
use function is_object;
use function is_string;
use function str_replace;
use function strrpos;
use function substr;

/**
 * Class OrmQueryBuilder
 *
 * @package Ems\Model\Database
 */
class OrmQueryBuilder
{
    /**
     * @var SchemaInspector
     */
    private $inspector;

    /**
     * @var string
     */
    private $dbSeparator = '__';

    public function __construct(SchemaInspector $inspector)
    {
        $this->inspector = $inspector;
    }

    /**
     * Create a new orm query.
     *
     * @param string $class
     *
     * @return OrmQuery
     */
    public function query($class)
    {

    }

    /**
     * @param OrmQuery $query
     * @param Query    $useThis (optional)
     *
     * @return Query
     */
    public function toSelect(OrmQuery $query, Query $useThis=null)
    {
        $table = $this->inspector->getStorageName($query->ormClass);
        $dbQuery = $this->dbQuery($query->ormClass, $useThis)->from($table);
        $keys = $this->inspector->getKeys($query->ormClass);
        $columns = $this->toColumns($keys, $table);
        $dbQuery->select(...$columns);

        $relations = $this->collectRelations($query);
        $relationMap = $this->buildRelationMap($query, $relations);

        $this->addConditions($query->conditions, $dbQuery->conditions, $relationMap);

        // $neededJoins = addNeededJoins caused by where/orderBy/with/eager
        // add manually added joins that were added by with()
        // translate eventually used orm names in where/order by
        // add columns/aliases caused by with()

        // That is all fine and can be solved, but we need a working
        // totalCount (not so difficult)
        // and iff we really want eager load to many relations we need a working
        // limit query (which I think is not easy)
        // so in any has many we must append()
        // just to make that clear: appendings() will be added inside the array
        // not on object level.
        // Then we MUST define a chunk size. In paginator it will be the page
        // size, without it will be something like 100.

        $this->addJoins($query, $dbQuery, $relationMap);

        // After all this join adding we have to append the missing appendings
        // to the orm query

        return $dbQuery;
    }

    /**
     * @param OrmQuery $query
     * @param array    $values
     * @param Query    $useThis (optional)
     *
     * @return Query
     */
    public function toInsert(OrmQuery $query, array $values, Query $useThis=null)
    {

    }

    /**
     * @param OrmQuery $query
     * @param array    $values
     * @param Query    $useThis (optional)
     *
     * @return Query
     */
    public function toUpdate(OrmQuery $query, array $values, Query $useThis=null)
    {

    }

    /**
     * @param OrmQuery $query
     * @param Query    $useThis (optional)
     *
     * @return Query
     */
    public function toDelete(OrmQuery $query, Query $useThis=null)
    {

    }

    /**
     * Ensure we have a database query to work with.
     *
     * @param string $class
     * @param Query  $dbQuery (optional)
     *
     * @return Query
     */
    protected function dbQuery($class, Query $dbQuery=null)
    {
        if ($dbQuery) {
            return $dbQuery;
        }
        $storageName = $this->inspector->getStorageName($class);
        return (new Query())->from($storageName);
    }

    protected function toColumns(array $keys, $tableOrAlias, $relationName='')
    {
        $columns = [];
        foreach ($keys as $key) {
            $column = "$tableOrAlias.$key";
            if ($relationName) {
                $column .= " AS {$relationName}__$key";
            }
            $columns[] = $column;
        }
        return $columns;
    }

    /**
     * @param Parentheses|Predicate[] $conditions
     * @param Parentheses             $dbConditions
     * @param array                   $relationMap (optional)
     */
    protected function addConditions($conditions, Parentheses $dbConditions, array $relationMap=[])
    {
        foreach ($conditions as $condition) {

            if ($condition instanceof Predicate) {
                $dbConditions->where($this->convertPredicate($condition, $relationMap));
                continue;
            }

            if (!$condition instanceof Parentheses) {
                throw new TypeException("Unknown condition type: " . Type::of($condition));
            }

            $childConditions = $dbConditions($condition->boolean);
            $this->addConditions($condition, $childConditions, $relationMap);

        }
    }

    protected function convertPredicate(Predicate $predicate, array $relationMap=[])
    {
        $leftColumn = $this->translatePath($predicate->left, $relationMap);
        $operator = $predicate->operator;
        $rightColumn = $predicate->right;

        if ($predicate->rightIsKey) {
            $rightColumn = $this->translatePath($rightColumn, $relationMap);
        }

        return (new Predicate($leftColumn, $operator, $rightColumn))
            ->rightIsKey($predicate->rightIsKey);
    }

    protected function collectRelations(OrmQuery $query)
    {
        $parents = [];

        if (count($query->conditions)) {
            $this->parentsFromItems($query->conditions, $parents);
        }

        if (count($query->orderBys)) {
            $this->parentsFromItems(array_keys($query->orderBys), $parents);
        }
        foreach ($query->withs as $parent) {
            $parents[] = $parent;
        }
        return array_values(array_unique($parents));
    }

    protected function buildRelationMap(OrmQuery $ormQuery, array $relations)
    {
        sort($relations);
        $map = [];

        foreach ($relations as $relationName) {
            $parts = explode('.', $relationName);
            $parentClass = $ormQuery->ormClass;
            $relation = null;
            foreach ($parts as $segment) {
                $relation = $this->inspector->getRelationship($parentClass, $segment);
                $parentClass = get_class($relation->owner);
            }
            if (!$relation) {
                throw new RuntimeException("Relation object $relationName not found.");
            }
            $map[$relationName] = [
                'path'      => $this->keyToColumn($relationName),
                'relation'  => $relation
            ];
        }

        return $map;
    }

    protected function addJoins(OrmQuery $ormQuery, Query $dbQuery, array $relationMap)
    {
        foreach ($relationMap as $name=>$map) {
            /** @var Relationship $relation */
            $relation = $map['relation'];
            // $this->inspector // $ormQuery->ormClass
            if ($relation->hasMany() || $relation->belongsToMany()) {
                $dbQuery->distinct(true);
            }

            $relatedClass = get_class($relation->related);
            $relatedTable = $this->inspector->getStorageName($relatedClass);
            $ownerClass = get_class($relation->owner);
            $ownerTable = $this->inspector->getStorageName($ownerClass);

            echo "\n$relatedTable.".$relation->relatedKey . " -> $ownerTable.$relation->ownerKey";
            $dbQuery->join($relatedTable)
                ->on("$ownerTable.$relation->ownerKey", "$relatedTable.$relation->relatedKey");
        }
    }

    /**
     * @param Predicate[]|Parentheses[]|Parentheses|string[] $items
     * @param array                                          $parents
     */
    protected function parentsFromItems($items, array &$parents)
    {
        foreach ($items as $item) {
            $this->parentsOfItem($item, $parents);
        }
    }

    protected function parentsOfItem($item, array &$parents)
    {
        if ($item instanceof Parentheses) {
            $this->parentsFromItems($item, $parents);
            return;
        }
        if ($item instanceof Predicate) {
            $this->parentsOfItem($item->left, $parents);
            return;
        }
        if ($item instanceof KeyExpression) {
            $this->parentsOfItem("$item", $parents);
            return;
        }
        if ($item instanceof Expression) {
            return;
        }
        if (!is_string($item)) {
            $this->parentsOfItem("$item", $parents);
        }
        list($parentPath, $key) = $this->parentAndKey($item);
        if ($parentPath) {
            $parents[] = $parentPath;
        }
    }

    protected function toStringIfDesired($expression)
    {
        if ($expression instanceof KeyExpression) {
            return $expression->toString();
        }

    }

    /**
     * @param string $key
     *
     * @return string[]
     */
    protected function parentAndKey($key)
    {
        $lastPointPos = strrpos($key, '.');
        if ($lastPointPos === false) {
            return ['', $key];
        }
        return [substr($key, 0, $lastPointPos), substr($key, $lastPointPos+1)];
    }

    /**
     * @param string $key
     * @param array $append
     *
     * @return string
     */
    protected function keyToColumn($key, ...$append)
    {
        $column = str_replace('.', $this->dbSeparator, $key);
        if (!$append) {
            return $column;
        }
        return $column . $this->dbSeparator . implode($this->dbSeparator, $append);
    }

    protected function translatePath($path, array $relationMap)
    {
        list($parent, $key) = $this->parentAndKey($path);
        if (!$parent) {
            return $key;
        }
        if (!isset($relationMap[$parent])) {
            throw new RuntimeException("RelationMap didnt contain relation '$parent'");
        }
        return $relationMap[$parent]['path'] . ".$key";
    }
}
