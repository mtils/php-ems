<?php
/**
 *  * Created by mtils on 14.09.18 at 11:28.
 **/

namespace Ems\Tree\Eloquent;


use Ems\Contracts\Core\Errors\NotFound;
use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Tree\CanHaveParent;
use Ems\Contracts\Tree\Node;
use Ems\Contracts\Tree\NodeRepository as NodeRepositoryContract;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Model\Database\PDOPrepared;
use Ems\Model\Eloquent\HookableRepository;
use Ems\Model\Eloquent\Model;
use Ems\Model\Eloquent\NotFoundException;
use Ems\Tree\GenericChildren;
use Ems\Tree\HierarchyMethods;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Query\Builder as Query;
use function array_fill;
use function is_numeric;
use function ksort;
use const SORT_NUMERIC;

class NodeRepository extends HookableRepository implements NodeRepositoryContract
{
    use HierarchyMethods;

    /**
     * @var Node
     */
    protected $currentParent;

    /**
     * @var int
     */
    protected $currentDepth = 0;

    /**
     * @var string
     */
    protected $pathKey = 'path';

    /**
     * @var string
     */
    protected $segmentKey = 'path_segment';

    /**
     * @var string
     */
    protected $parentIdKey = 'parent_id';

    /**
     * @var PDOPrepared
     */
    protected $ancestorsStatement;

    /**
     * @inheritDoc
     */
    public function __construct(EloquentModel $model, array $attributes=[])
    {
        $this->checkIsNode($model);
        parent::__construct($model);
        $this->_maxDepth = 10;
        $this->currentParent = isset($attributes['parent']) ? $attributes['parent'] : null;
        $this->currentDepth = isset($attributes['depth']) ? $attributes['depth'] : null;
    }

    /**
     * {@inheritdoc}
     *
     * Reimplemented just to correct the return type hint.
     *
     * @param mixed $id
     * @param mixed $default (optional)
     *
     * @return Node|null
     **/
    public function get($id, $default = null)
    {
        /** @var Node $node */
        $node = parent::get($id, $default);
        return $node;
    }

    /**
     * {@inheritdoc}
     *
     * Reimplemented just to correct the return type hint.
     *
     * @param mixed $id
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound If no model was found by the id
     *
     * @return Node
     **/
    public function getOrFail($id)
    {
        /** @var Node $node */
        $node = parent::getOrFail($id);
        return $node;
    }

    /**
     * {@inheritdoc}
     *
     * Reimplemented just to correct the return type hint.
     * @param array $attributes
     *
     *
     * @return Node The instantiated node
     **/
    public function make(array $attributes = [])
    {
        /** @var Node $node */
        $node = parent::make($attributes);
        return $node;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @return Node The created resource
     **/
    public function store(array $attributes)
    {
        /** @var Node $node */
        $node = parent::store($attributes);
        return $node;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $depth default:1
     *
     * @return self
     */
    public function recursive($depth = 1)
    {
        return $this->replicate($this->model, ['depth' => $depth]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string        $path
     * @param CanHaveParent $default [optional]
     *
     * @return CanHaveParent
     */
    public function getByPath($path, CanHaveParent $default = null)
    {
        $query = $this->model->newQuery();

        $this->callBeforeListeners('getByPath', [$query]);

        if (!$node = $this->performGetByPath($query, $path)) {
            return $default;
        }

        if (!$this->currentDepth) {
            $this->callAfterListeners('getByPath', [$node]);
            return $node;
        }

        if ($this->currentDepth != 1) {
            throw new NotImplementedException('Currently depths deeper that one are not supported.');
        }




        /** @var Node $node */

        $children = $node->getChildren();

        foreach ($this->recursive(0)->children($node) as $child) {
            $children->append($child);
        }

        $this->callAfterListeners('getByPath', [$node]);

        return $node;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     *
     * @return CanHaveParent
     *
     * @throws NotFound
     */
    public function getByPathOrFail($path)
    {
        if ($node = $this->getByPath($path)) {
            return $node;
        }

        throw new NotFoundException("No node under path (column:$this->pathKey) '$path' found'");
    }

    /**
     * {@inheritdoc}
     *
     * @param string $segment
     *
     * @return CanHaveParent[]
     */
    public function findBySegment($segment)
    {
        if ($this->currentDepth) {
            throw new NotImplementedException('Currently depth is not supported for findBySegment().');
        }

        return $this->model->newQuery()
                    ->where($this->segmentKey, $segment)
                    ->get()->all();

    }

    public function children(Node $parent)
    {
        if ($this->currentDepth) {
            throw new NotImplementedException('Currently depth is not supported for children().');
        }
        $models = $this->model->newQuery()
                              ->where($this->parentIdKey, $parent->getId())
                              ->get()->all();
        return new GenericChildren($models);
    }

    /**
     * @inheritDoc
     */
    public function parent(CanHaveParent $child)
    {
        if ($child->isRoot()) {
            return null;
        }

        $this->checkIsModel($child);

        /** @var Model $child */
        return $this->getOrFail($child->getAttribute($this->parentIdKey));
    }

    /**
     * @inheritDoc
     */
    public function ancestors(CanHaveParent $child)
    {

        // Not from database and no parentId
        if (!$child->getId() && !$child->getParentId()) {
            return $this->collectAncestors($child);
        }

        $ancestors = [];
        $parentsById = [];

        $bindings = array_fill(0, $this->_maxDepth, $child->getId());
        $idKey = $this->model->getKeyName();

        foreach ($this->getAncestorStatement()->bind($bindings) as $parentRow) {

            $data = $this->pdoResultToArray($parentRow);
            $distance = $data['_child_distance'];
            unset($data['_child_distance']);

            $node = $this->model->newFromBuilder($data);
            $ancestors[$distance] = $node;
            $parentsById[$data[$idKey]] = $node;

        }

        /**
         * Set all parent relations
         *
         * @var int $id
         * @var Node $node
         */
        foreach ($parentsById as $id=>$node) {
            $parentId = $node->getParentId();
            if (isset($parentsById[$parentId])) {
                $node->setParent($parentsById[$parentId]);
            }
        }

        if (isset($parentsById[$child->getParentId()])) {
            $child->setParent($parentsById[$child->getParentId()]);
        }

        ksort($ancestors, SORT_NUMERIC);

        // Return a clean array
        return array_values($ancestors);

    }

    /**
     * @inheritDoc
     */
    public function asChildOf(Node $node)
    {
        return $this->replicate($this->model, ['parent' => $node]);
    }

    /**
     * @return string
     */
    public function getPathKey()
    {
        return $this->pathKey;
    }

    /**
     * Set the key (model attribute) of the path column.
     * If a key is set this repository will store the complete paths in the
     * database and allows passing the path via store(), update() and fill().
     * If no path key is set you can neither pass the path nor c
     *
     * @param string $pathKey
     *
     * @return $this
     */
    public function setPathKey($pathKey)
    {
        $this->pathKey = $pathKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getParentIdKey()
    {
        return $this->parentIdKey;
    }

    /**
     * Set the key (model attribute) of the parent id column.
     *
     * @param string $parentIdKey
     *
     * @return $this
     */
    public function setParentIdKey(string $parentIdKey)
    {
        $this->parentIdKey = $parentIdKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getSegmentKey(): string
    {
        return $this->segmentKey;
    }

    /**
     * @param string $segmentKey
     *
     * @return $this
     */
    public function setSegmentKey(string $segmentKey)
    {
        $this->segmentKey = $segmentKey;
        return $this;
    }

    /**
     * @param EloquentModel $model
     *
     * @param array $attributes [optional]
     *
     * @return static
     */
    protected function replicate(EloquentModel $model, array $attributes=[])
    {
        $next = new static($model, $attributes);
        $next->setParentIdKey($this->getParentIdKey())
             ->setSegmentKey($this->getSegmentKey())
             ->setPathKey($this->getPathKey());

        return $next;
    }

    /**
     * @inheritDoc
     */
    protected function performGet(Builder $query, $id)
    {
        if (!$this->currentDepth) {
            return parent::performGet($query, $id);
        }

        if ($this->currentDepth != 1) {
            throw new NotImplementedException('Currently depths deeper that one are not supported.');
        }

        $nodes = $query->where($this->model->getKeyName(), $id)
                       ->orWhere($this->parentIdKey, $id)
                       ->get();

        $searchedNode = null;

        // First search the parent node (the searched one)
        /** @var Node $node */
        foreach ($nodes as $node) {
            if ($node->getId() == $id) {
                $searchedNode = $node;
            }
        }

        if (!$searchedNode) {
            return null;
        }

        // Then assign its children
        /** @var Node $node */

        $searchedNodeChildren = $searchedNode->getChildren();

        foreach ($nodes as $node) {
            if ($node->getParentId() == $searchedNode->getId()) {
                //$searchedNode->addChild($node);
                $searchedNodeChildren->append($node);
            }
        }

        return $searchedNode;
    }

    protected function performGetByPath(Builder $query, $path)
    {
        return $query->where($this->pathKey, $path)->first();
    }

    /**
     * @inheritDoc
     */
    protected function performMake(array $attributes)
    {

        /** @var Node $node */
        $node = parent::performMake($attributes);


        if ($this->currentParent) {
            $node->setParent($this->currentParent);
        }

        return $node;

    }

    /**
     * @inheritDoc
     */
    protected function performFill(EloquentModel $model, array $attributes)
    {
        $this->checkIsNode($model);


        parent::performFill($model, $attributes);

        /** @var Node $model */
        if ($this->currentParent) {
            $model->setParent($this->currentParent);
        }

    }

    /**
     * @inheritDoc
     */
    protected function performSave(EloquentModel $model)
    {
        $this->checkIsNode($model);

        /** @var EloquentNode $model */
        if ($this->currentParent) {
            $model->setParent($this->currentParent);
        }

        if (!$this->pathKey || $model->getAttribute($this->pathKey)) {
            return parent::performSave($model);
        }

        $this->addPathIfNeeded($model);

        return parent::performSave($model);
    }

    /**
     * Calculate and add a stored path attribute if a pathKey exists.
     *
     * @param EloquentNode $node
     */
    protected function addPathIfNeeded(EloquentNode $node)
    {
        // No path column
        if (!$this->pathKey) {
            return;
        }

        // Path was passed
        if ($node->getAttribute($this->pathKey)) {
            return;
        }

        $segment = $node->getAttribute($this->segmentKey);

        if ($node->isRoot() && $segment) {
            $node->setAttribute($this->pathKey, '/' . $this->cleanSegment($segment));
            return;
        }

        $parent = $this->guessParent($node);

        $parentPath = $parent->getPath();

        $node->setAttribute($this->pathKey, rtrim($parentPath, '/') . '/' . $this->cleanSegment($node->getPathSegment()));

    }

    /**
     * @param CanHaveParent $node
     *
     * @return Node|null
     */
    protected function guessParent(CanHaveParent $node)
    {
        // First priority is always $this->asChildOf($parent)
        if ($this->currentParent) {
            return $this->currentParent;
        }

        if (!$node->hasParent()) {
            return null;
        }

        if ($parent = $node->getParent()) {
            return $parent;
        }

        return $this->getOrFail($node->getParentId());

    }

    /**
     * @param EloquentModel $model
     *
     * @return EloquentModel
     */
    protected function checkIsNode(EloquentModel $model)
    {
        if ($model instanceof Node) {
            return $model;
        }

        throw new TypeException('The model has to implement the Node interface');
    }

    /**
     * Build the query to retrieve the parents from database.
     *
     * @param int $childId
     *
     * @return PDOPrepared
     */
    protected function buildAncestorQuery($childId)
    {
        // We don't use Eloquent here because of endless subquery nesting
        $grammar = $this->select()->getGrammar();

        $table = $this->model->getTable();
        $idKey = $this->model->getKeyName();
        $idQueryColumn = $grammar->wrap("$table.$idKey");

        $childQuery = $this->select(["$table.$this->parentIdKey"])
                           ->where("$table.$idKey", $childId);

        $last = $childQuery;
        $queries = [];

        for ($i=1; $i <= $this->_maxDepth; $i++) {

            $queries[] = $this->buildOuterAncestorQuery($i, $last, $table, $idQueryColumn)->toSql();

            $last = $this->select([$this->parentIdKey])->whereRaw("$idQueryColumn = (" . $last->toSql() . ')');

        }

        $query = implode("\nUNION\n", $queries);

        $pdoStatement = $this->model->getConnection()->getPdo()->prepare($query);

        return new PDOPrepared($pdoStatement, $query);

    }

    /**
     * Build a prepared statement for ancestor queries.
     *
     * @return PDOPrepared
     */
    protected function getAncestorStatement()
    {
        if (!$this->ancestorsStatement) {
            $this->ancestorsStatement = $this->buildAncestorQuery(0);
        }
        return $this->ancestorsStatement;
    }

    protected function buildOuterAncestorQuery($level, Query $childQuery, $table, $idQueryColumn)
    {
        $outerQuery = $this->model->newQuery()->getQuery();
        $con = $outerQuery->getConnection();

        $outerQuery->select(["$table.*", $con->raw("$level as _child_distance")])
                   ->whereRaw("$idQueryColumn = (" . $childQuery->toSql() . ')');

        return $outerQuery;
    }

    /**
     * Create a new base query builder (not Eloquent)
     *
     * @param array $columns (optional)
     *
     * @return Query
     */
    protected function select($columns=[])
    {
        $query = $this->model->newQuery()->getQuery();
        if ($columns) {
            $query->select($columns);
        }
        return $query;
    }

    /**
     * @param \stdClass|array $row
     *
     * @return array
     */
    protected function pdoResultToArray($row)
    {
        if (is_object($row)) {
            $row = (array)$row;
        }

        $data = [];

        foreach ($row as $key=>$value) {
            if (!is_numeric($key)) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}