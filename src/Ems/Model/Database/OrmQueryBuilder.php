<?php

/**
 *  * Created by mtils on 04.04.20 at 13:16.
 **/

namespace Ems\Model\Database;

use Ems\Contracts\Core\Connection;
use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Core\Expression;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Contracts\Model\Database\Parentheses;
use Ems\Contracts\Model\Database\Predicate;
use Ems\Contracts\Model\Database\Query;
use Ems\Contracts\Model\Database\SQLExpression;
use Ems\Contracts\Model\OrmQuery;
use Ems\Contracts\Model\OrmQueryRunner;
use Ems\Contracts\Model\Relationship;
use Ems\Contracts\Model\Result;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\KeyExpression;
use RuntimeException;
use Ems\Contracts\Model\Database\Connection as DbConnection;

use function array_keys;
use function array_map;
use function array_unique;
use function class_exists;
use function explode;
use function get_class;
use function implode;
use function in_array;
use function is_string;
use function str_replace;
use function strpos;
use function strrpos;
use function substr;

/**
 * Class OrmQueryBuilder
 *
 * @package Ems\Model\Database
 */
class OrmQueryBuilder implements OrmQueryRunner
{
    const TO_MANY = 'to_many';

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
     * {@inheritDoc}
     *
     * @param Connection $connection
     * @param OrmQuery $query
     *
     * @return Result
     */
    public function retrieve(Connection $connection, OrmQuery $query)
    {
        $dbQuery = $this->toSelect($query);
        $connection = $this->con($connection);

        $result = $this->newResult($connection, $query, $dbQuery);

        return $result->provideCountQuery(function () use ($query, $connection, $dbQuery) {
            return $this->toCountQuery($query, $connection, $dbQuery);
        });

    }

    /**
     * {@inheritDoc}
     *
     * @param Connection $connection
     * @param string|OrmQuery $ormClass
     * @param array $values
     *
     * @return int|string|array
     */
    public function create(Connection $connection, $ormClass, array $values)
    {
        // TODO: Implement create() method.
    }

    /**
     * {@inheritDoc}
     *
     * @param Connection $connection
     * @param OrmQuery $query
     * @param array $values
     *
     * @return int
     */
    public function update(Connection $connection, OrmQuery $query, array $values)
    {
        // TODO: Implement update() method.
    }

    /**
     * {@inheritDoc}
     *
     * @param Connection $connection
     * @param OrmQuery $query
     *
     * @return int
     */
    public function delete(Connection $connection, OrmQuery $query)
    {
        // TODO: Implement delete() method.
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
        return new OrmQuery($class);
    }

    /**
     * Create a database query that selects according to $query. If the queried
     * relations will create multiple rows of the "main queried object" it will
     * split the query in two. The second query will be attached to the returned.
     *
     * @param OrmQuery $query
     * @param Query    $useThis (optional)
     *
     * @return Query
     */
    public function toSelect(OrmQuery $query, Query $useThis=null)
    {
        $table = $this->inspector->getStorageName($query->ormClass);
        $dbQuery = $this->dbQuery($query->ormClass, $useThis)->from($table);
        $relationMap = $this->buildRelationMap($query, $this->collectRelations($query));

        $this->buildSelect($query, $dbQuery, $relationMap);

        if (!$this->containsHasMany($relationMap)) {
            return $dbQuery;
        }

        $toManyQuery = $this->dbQuery($query->ormClass);
        $this->buildSelect($query, $toManyQuery, $relationMap, true);

        return $dbQuery->attach(self::TO_MANY, $toManyQuery);

    }

    /**
     * @param OrmQuery      $query
     * @param array         $values
     * @param Query|null    $useThis (optional)
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

    public function toCountQuery(OrmQuery $ormQuery, DbConnection $connection, Query $dbQuery=null)
    {
        $primaryKeys = $this->inspector->primaryKey($ormQuery->ormClass);
        $table = $this->inspector->getStorageName($ormQuery->ormClass);
        $dbQuery = $dbQuery ?: $this->toSelect($ormQuery);
        $dialect = $connection->dialect();
        $dialect = $dialect instanceof Dialect ? $dialect : SQL::dialect($dialect);

        $countQuery = clone $dbQuery;
        $countQuery->columns = [];

        $quoted = array_map(function ($key) use ($dialect, $table) {
            return $dialect->quote("$table.$key", Dialect::NAME);
        }, (array)$primaryKeys);

        $expression = new SQLExpression('COUNT(' . implode(',', $quoted) . ') as total_count');

        return $countQuery->select($expression)->distinct(true);
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

    protected function buildSelect(OrmQuery $query, Query $dbQuery, RelationMap $map, $toMany=false)
    {
        $keys = $toMany ? (array)$this->inspector->primaryKey($query->ormClass) : $this->inspector->getKeys($query->ormClass);
        $columns = $this->toColumns($keys, $dbQuery->table);
        $dbQuery->select(...$columns);

        $this->addRelationalColumns($query, $dbQuery, $map, $toMany);
        $this->addConditions($query->conditions, $dbQuery->conditions, $map);
        $this->addJoins($query, $dbQuery, $map);
    }

    protected function addRelationalColumns(OrmQuery $query, Query $dbQuery, RelationMap $relationMap, $withToMany=false)
    {
        if (!$query->withs) {
            return;
        }

        $added = [];

        foreach ($query->withs as $relationPath) {

            if (in_array($relationPath, $added)) {
                continue;
            }

            $segments = explode('.', $relationPath);
            $segmentStack = [];

            foreach ($segments as $segment) {

                $segmentStack[] = $segment;
                $currentPath = implode('.', $segmentStack);

                if (in_array($currentPath, $added)) {
                    continue;
                }

                if (!$relationShip = $relationMap->relation($currentPath)) {
                    throw new KeyNotFoundException("Relation $currentPath needed by query but missing in relationMap");
                }

                $createsMultipleRows = $this->createsMultipleRows($currentPath, $relationMap);

                if ($createsMultipleRows && !$withToMany) {
                    continue;
                }

                if (!$createsMultipleRows && $withToMany) {
                    continue;
                }

                $relatedKeys = $this->inspector->getKeys(get_class($relationShip->related));
                $relationAlias = $this->keyToColumn($currentPath);

                $relatedColumns = array_map(function ($key) use ($currentPath, $relationAlias) {
                    return new KeyExpression("$relationAlias.$key", $relationAlias . "__$key");
                }, $relatedKeys);

                $dbQuery->select(...$relatedColumns);
                $added[] = $currentPath;

            }

        }

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
     * @param RelationMap             $relationMap
     */
    protected function addConditions($conditions, Parentheses $dbConditions, RelationMap $relationMap)
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

    protected function convertPredicate(Predicate $predicate, RelationMap $relationMap)
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

    /**
     * @param OrmQuery $ormQuery
     * @param array $relations
     *
     * @return RelationMap
     */
    protected function buildRelationMap(OrmQuery $ormQuery, array $relations)
    {
        sort($relations);
        $map = new RelationMap($ormQuery->ormClass);

        foreach ($relations as $relationName) {
            $parts = explode('.', $relationName);
            $parentClass = $ormQuery->ormClass;
            $relation = null;

            $nameStack = [];
            foreach ($parts as $segment) {

                $nameStack[] = $segment;
                $currentRelationName = implode('.', $nameStack);

                $relation = $this->inspector->getRelationship($parentClass, $segment);
                $parentClass = get_class($relation->related);

                if (!$relation) {
                    throw new RuntimeException("Relation object $currentRelationName not found.");
                }

                $map->addRelation($currentRelationName, $relation, $this->keyToColumn($currentRelationName));
            }

        }

        return $map;
    }

    protected function containsHasMany(RelationMap $relationMap)
    {
        foreach($relationMap as $relation) {
            /** @var Relationship $relation */
            if ($relation->hasMany) {
                return true;
            }
        }
        return false;
    }

    protected function addJoins(OrmQuery $ormQuery, Query $dbQuery, RelationMap $relationMap)
    {
        /** @var Relationship $relation */
        foreach ($relationMap as $name=>$relation) {

            if ($relation->hasMany || $relation->belongsToMany) {
                $dbQuery->distinct(true);
            }

            $this->addJoin($ormQuery, $dbQuery, $relation, $name);
        }
    }

    protected function addJoin(OrmQuery $ormQuery, Query $dbQuery, Relationship $relation, $name)
    {
        $lastPointPos = strrpos($name, '.');
        $relationParent = $lastPointPos ? substr($name, 0, $lastPointPos) : '';

        $relationName = $relationParent ? ($relationParent . '__' . $relation->name) : $relation->name;

        $relatedClass = get_class($relation->related);
        $relatedTable = $this->inspector->getStorageName($relatedClass);
        $ownerClass = get_class($relation->owner);
        $ownerTable = $this->inspector->getStorageName($ownerClass);

        $needsAlias = $relationName != $relatedTable;
        $tableAlias = $needsAlias ? $relationName : $relatedTable;

        if (!$junction = $relation->junction) {
            $leftAlias = $relationParent ? $relationParent : $ownerTable;
            $join = $dbQuery->join($relatedTable)->left()
                ->on("$leftAlias.$relation->ownerKey", "$tableAlias.$relation->relatedKey");

            if ($needsAlias) {
                $join->as($tableAlias);
            }
            return;
        }

        $junctionTable = $this->junctionIsClass($junction) ? $this->inspector->getStorageName($junction) : $junction;
        $junctionAlias = $tableAlias . '_pivot';

        $dbQuery->join($junctionTable)
                ->as($junctionAlias)
                ->left()
                ->on("$ownerTable.$relation->ownerKey", "$junctionAlias.$relation->junctionOwnerKey");

        $join = $dbQuery->join($relatedTable)
                        ->left()
                        ->on("$junctionAlias.$relation->junctionRelatedKey", "$tableAlias.$relation->relatedKey");

        if ($needsAlias) {
            $join->as($tableAlias);
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

    /**
     * Translate the relational path (projects.files.parent.name) to the
     * path it will be named in the database (projects__files__parent__name).
     *
     * @param string      $path
     * @param RelationMap $relationMap
     *
     * @return string
     */
    protected function translatePath($path, RelationMap $relationMap)
    {
        list($parent, $key) = $this->parentAndKey($path);
        if (!$parent) {
            return $this->inspector->getStorageName($relationMap->getOrmClass()) . ".$key";
        }
        if (!$parentPath = $relationMap->path($parent)) {
            throw new RuntimeException("RelationMap did not contain relation '$parent'");
        }
        return "$parentPath.$key";
    }

    /**
     * Check if the junction is a class or a storage name.
     *
     * @param string $junction
     *
     * @return bool
     */
    protected function junctionIsClass($junction)
    {
        if (strpos($junction, '_') !== false) {
            return false;
        }
        if (strpos($junction, '\\') !== false) {
            return true;
        }
        return class_exists($junction);
    }

    /**
     * Check if the join/select of a relation will create multiple rows.
     *
     * @param string      $relationPath
     * @param RelationMap $map
     *
     * @return bool
     */
    protected function createsMultipleRows($relationPath, RelationMap $map)
    {
        $segments = explode('.', $relationPath);
        $segmentStack = [];

        foreach ($segments as $segment) {
            $segmentStack[] = $segment;
            $currentPath = implode('.', $segmentStack);
            if ($map->relation($currentPath)->hasMany) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param DbConnection $connection
     * @param OrmQuery $ormQuery
     * @param Query $query
     *
     * @return DbOrmQueryResult
     */
    protected function newResult(DbConnection $connection, OrmQuery $ormQuery, Query $query)
    {

        return (new DbOrmQueryResult())
            ->setOrmQuery($ormQuery)
            ->setDbQuery($query)
            ->setConnection($connection)
            ->setInspector($this->inspector);
    }

    /**
     * @param Connection $connection
     *
     * @return DbConnection
     */
    protected function con(Connection $connection)
    {
        if (!$connection instanceof DbConnection) {
            throw new TypeException('I can only work with ' . DbConnection::class);
        }
        return $connection;
    }
}
