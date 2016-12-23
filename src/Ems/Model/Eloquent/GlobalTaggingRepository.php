<?php

namespace Ems\Model\Eloquent;

use InvalidArgumentException;
use Ems\Contracts\Model\Relation\Tag\GlobalTaggingRepository as RepositoryContract;
use Ems\Contracts\Model\Relation\Tag\HoldsTags;
use Ems\Contracts\Model\Relation\Tag\Tag as TagContract;
use Ems\Contracts\Core\AppliesToResource;

class GlobalTaggingRepository implements RepositoryContract
{
    public $relationTable = 'tag_relations';

    public $tagIdKey = 'tag_id';

    public $foreignIdKey = 'foreign_id';

    public $resourceNameKey = 'resource';

    /**
     * @var \Ems\Model\Eloquent\Tag
     **/
    protected $model;

    /**
     * @var string
     **/
    protected $resourceName;

    public function __construct(Tag $tag = null, $resourceName = null)
    {
        $this->model = $tag;
        $this->resourceName = $resourceName;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Traversable
     **/
    public function all()
    {
        $query = $this->model->newQuery()
                             ->select($this->model->getTable().'.*')
                             ->orderBy($this->model->getNameKey())
                             ->distinct();

        if ($this->resourceName) {
            $query->join($this->relationTable, $this->tagKeyName(), '=', $this->model->getTable().'.'.$this->model->getKeyName());
            $query->where($this->resourceNameKey, $this->resourceName);
        }

        return $query->get();
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Model\Relation\HoldsTags|\Traversable $holders
     *
     * @return self
     **/
    public function attachTags(&$holders)
    {
        $tagHolders = $holders instanceof HoldsTags ? [$holders] : $holders;

        if (!$holder = $this->findFirst($tagHolders)) {
            return $this;
        }

        $resourceName = $holder->resourceName();

        if (!$tags = $this->tagsByForeignId($this->collectIds($tagHolders), $resourceName)) {
            return;
        }

        foreach ($tagHolders as $holder) {
            $foreignId = $holder->getKey();
            if (!isset($tags[$foreignId])) {
                continue;
            }
            foreach ($tags[$foreignId] as $tag) {
                $holder->attachTag($tag);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Model\Relation\HoldsTags $holder
     *
     * @return self
     **/
    public function syncTags(HoldsTags $holder)
    {
        $holderRelations = [];

        if (!$holder instanceof AppliesToResource) {
            throw new InvalidArgumentException('Holders have to be instanceof AppliesToResource for GlobalTaggingRepository');
        }

        $this->assureExistingTags($holder);

        $settedIds = $this->collectIds($holder->getTags());
        $storedIds = $this->collectIds($this->tagsByForeignId([$holder->getId()], $holder->resourceName(), true));

        list($attach, $unchanged, $detach) = $this->sortByDifference($storedIds, $settedIds);

        if ($detach) {
            $query = $this->newPivotQuery()
                          ->where($this->resourceNameKey, $holder->resourceName())
                          ->where($this->foreignIdKey, $holder->getKey())
                          ->whereIn($this->tagIdKey, $detach)
                          ->delete();
        }

        if ($attach) {
            $query = $this->newPivotQuery();
            $records = [];
            foreach ($attach as $attachId) {
                $records[] = $this->createPivotValues($holder, $attachId);
            }

            $query->insert($records);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return \Ems\Contracts\Model\Relation\Tag
     **/
    public function make($name)
    {
        return $this->model->newInstance([$this->model->getNameKey() => $name]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return \Ems\Contracts\Model\Relation\Tag
     **/
    public function create($name)
    {
        return $this->model->create([$this->model->getNameKey() => $name]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return \Ems\Contracts\Model\Relation\Tag
     **/
    public function getByNameOrCreate($name)
    {
        if ($tag = $this->getByName($name)) {
            return $tag;
        }

        return $this->create($name);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $id
     *
     * @return \Ems\Contracts\Model\Relation\Tag
     **/
    public function getOrFail($id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Model\Relation\Tag
     *
     * @return self
     **/
    public function delete(TagContract $tag)
    {
        $tag->delete();

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|\Ems\Contracts\Core\AppliesToResource $resource
     *
     * @return self
     **/
    public function by($resource)
    {
        $resourceName = $resource instanceof AppliesToResource ? $resource->resourceName() : $resource;

        return new static($this->model, $resourceName);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function resourceNames()
    {
        return $this->newPivotQuery()
                    ->select($this->resourceNameKey)
                    ->distinct()
                    ->lists($this->resourceNameKey);
    }

    protected function getByName($name)
    {
        $query = $this->model->newQuery()
                      ->where($this->model->getNameKey(), $name)
                      ->first();
    }

    protected function createPivotValues(HoldsTags $holder, $tagId)
    {
        return [
            $this->tagIdKey        => $tagId,
            $this->foreignIdKey    => $holder->getKey(),
            $this->resourceNameKey => $holder->resourceName(),
        ];
    }

    protected function assureExistingTags(HoldsTags $holder)
    {
        foreach ($holder->getTags() as $tag) {
            if (!$tag->exists) {
                $tag->save();
            }
        }
    }

    protected function collectIds($holders)
    {
        $ids = [];
        foreach ($holders as $holder) {
            $ids[] = $holder->getKey();
        }

        return $ids;
    }

    protected function newPivotQuery()
    {
        return $this->model->newQuery()->getQuery()->from($this->relationTable);
    }

    protected function tagsByForeignId(array $ids, $resourceName, $onlyFirst = false)
    {
        $query = $this->model->newQuery()
                      ->join($this->relationTable, $this->tagKeyName(), '=', $this->model->getTable().'.'.$this->model->getKeyName())
                      ->where("{$this->relationTable}.{$this->resourceNameKey}", $resourceName)
                      ->whereIn($this->foreignKeyName(), $ids);

        $byId = [];

        foreach ($query->get() as $tag) {
            $foreignId = $tag->getAttribute($this->foreignIdKey);
            if (!isset($byId[$foreignId])) {
                $byId[$foreignId] = [];
            }
            $byId[$foreignId][] = $tag;
        }
        if (!$onlyFirst || !count($byId)) {
            return $byId;
        }
        reset($byId);

        return $byId[key($byId)];
    }

    protected function sortByDifference(array $storedIds, array $passedIds)
    {
        $attached = [];
        $unchanged = [];
        $detached = [];

        foreach ($storedIds as $storedId) {
            if (!in_array($storedId, $passedIds)) {
                $detached[] = $storedId;
                continue;
            }
            $unchanged[] = $storedId;
        }

        foreach ($passedIds as $passedId) {
            if (!in_array($passedId, $storedIds)) {
                $attached[] = $passedId;
            }
        }

        return [$attached, $unchanged, $detached];
    }

    protected function tagKeyName()
    {
        return $this->relationTable.'.'.$this->tagIdKey;
    }

    protected function foreignKeyName()
    {
        return $this->relationTable.'.'.$this->foreignIdKey;
    }

    protected function findFirst($traversable)
    {
        foreach ($traversable as $item) {
            return $item;
        }
    }
}
