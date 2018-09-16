<?php
/**
 *  * Created by mtils on 14.09.18 at 11:28.
 **/

namespace Ems\Tree\Eloquent;


use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Tree\Node;
use Ems\Contracts\Tree\NodeRepository as NodeRepositoryContract;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Tree\GenericChildren;
use Ems\Model\Eloquent\HookableRepository;
use Ems\Model\Eloquent\Model;
use Ems\Model\Eloquent\NotFoundException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class NodeRepository extends HookableRepository implements NodeRepositoryContract
{
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
    protected $parentIdKey = 'parent_id';

    /**
     * @inheritDoc
     */
    public function __construct(EloquentModel $model, array $attributes=[])
    {
        $this->checkIsNode($model);
        parent::__construct($model);
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
     * @inheritDoc
     */
    public function recursive($depth = 1)
    {
        return $this->replicate($this->model, ['depth' => $depth]);
    }

    /**
     * @inheritDoc
     */
    public function getByPath($path, Node $default = null)
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
        foreach ($this->recursive(0)->children($node) as $child) {
            $node->addChild($child);
        }

        $this->callAfterListeners('getByPath', [$node]);

        return $node;
    }

    /**
     * @inheritDoc
     */
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
    public function parent(Node $child)
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
    public function getByPathOrFail($path)
    {
        if ($node = $this->getByPath($path)) {
            return $node;
        }

        throw new NotFoundException("No node under path '$path' found'");
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
     * @param EloquentModel $model
     *
     * @param array $attributes [optional]
     *
     * @return static
     */
    protected function replicate(EloquentModel $model, array $attributes=[])
    {
        return new static($model, $attributes);
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
        foreach ($nodes as $node) {
            if ($node->getParentId() == $searchedNode->getId()) {
                $searchedNode->addChild($node);
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

        /** @var Node $model */
        if ($this->currentParent) {
            $model->setParent($this->currentParent);
        }

        return parent::performSave($model);
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

}