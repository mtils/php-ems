<?php


namespace Ems\Model\Eloquent;

use Illuminate\Database\Eloquent\Model;

class TagQueryHelper
{

    public $relationTable = 'tag_relations';

    public $tagIdKey = 'tag_id';

    public $foreignIdKey = 'foreign_id';

    public $resourceNameKey = 'resource';

    public $resourceName = 'tags';

    /**
     * @var \Illuminate\Database\Eloquent\Model
     **/
    protected $tagModel;

    /**
     * @param \Illuminate\Database\Eloquent\Model $tagModel
     **/
    public function __construct(Model $tagModel)
    {
        $this->tagModel = $tagModel;
    }

    public function addTagFilter(Model $model, $query, array $tags)
    {
        $query->join($this->relationTable, $model->getTable() . '.' . $model->getKeyName(), '=', $this->foreignKeyName());
        $query->where($this->resourceNameKey, $model->resourceName());
        $query->whereIn($this->tagKeyName(), $this->tagIds($tags));
    }

    protected function tagKeyName()
    {
        return $this->relationTable . '.' . $this->tagIdKey;
    }

    protected function foreignKeyName()
    {
        return $this->relationTable. '.' . $this->foreignIdKey;
    }

    protected function tagIds($tags)
    {
        $tagIds = [];

        foreach ($tags as $tag) {
            $tagIds[] = $tag instanceof Model ? $tag->getKey() : (int)$tag;
        }

        return $tagIds;

    }

}