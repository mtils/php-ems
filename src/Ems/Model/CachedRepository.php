<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 12.11.17
 * Time: 07:25
 */

namespace Ems\Model;

use Ems\Contracts\Cache\Cache;
use Ems\Contracts\Core\Identifiable;
use Ems\Contracts\Model\Repository;
use Ems\Core\Exceptions\ResourceNotFoundException;
use Ems\Contracts\Core\Type;

/**
 * Class CachedRepository
 *
 * This repository is a proxy which uses the cache to cache any created
 * object.
 * I would mostly not recommend to put objects into the cache. Better cache
 * later (in the view).
 * But in cases of object, which do not change often this could be done.
 *
 * @package Ems\Model
 */
class CachedRepository implements Repository
{

    /**
     * @var Repository
     */
    protected $parent;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var Identifiable
     */
    protected $prototype;

   /**
     * @param Repository $parent
     * @param Cache      $cache
     */
    public function __construct(Repository $parent, Cache $cache)
    {
        $this->parent = $parent;
        $this->cache = $cache;
    }

    /**
     * @inheritdoc
     *
     * Put the model on every retrieval into cache.
     *
     * @param mixed $id
     * @param mixed $default (optional)
     *
     * @return mixed
     **/
    public function get($id, $default = null)
    {

        $cacheId = $this->cache->key([$this->prototype(), $id]);

        if ($this->cache->has($cacheId)) {
            return $this->cache->get($cacheId);
        }

        if ($model = $this->parent->get($id)) {
            $this->cache->put($cacheId, $model);
        }

        return $model ? $model : $default;
    }

    /**
     * @inheritdoc
     *
     * @param mixed $id
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     *
     * @return mixed
     **/
    public function getOrFail($id)
    {

        if ($model = $this->get($id)) {
            return $model;
        }

        $typeName = Type::of($this->prototype());

        throw new ResourceNotFoundException("$typeName #$id not found.");
    }

    /**
     * @inheritdoc
     *
     * @param array $attributes
     *
     * @return \Ems\Contracts\Core\Identifiable The instantiated resource
     **/
    public function make(array $attributes = [])
    {
        return $this->parent->make($attributes);
    }

    /**
     * @inheritdoc
     *
     * @param array $attributes
     *
     * @return \Ems\Contracts\Core\Identifiable The created resource
     **/
    public function store(array $attributes)
    {
        $model = $this->parent->store($attributes);
        $this->cache->put($model);
        return $model;
    }

    /**
     * @inheritdoc
     *
     * @param \Ems\Contracts\Core\Identifiable $model
     * @param array $attributes
     *
     * @return bool if attributes where changed after filling
     **/
    public function fill(Identifiable $model, array $attributes)
    {
        return $this->parent->fill($model, $attributes);
    }

    /**
     * @inheritdoc
     *
     * It is assumed here, that the parent repository just calls $this->save()
     * somewhere inside update!
     * So this method just forwards to parent.
     *
     * @param \Ems\Contracts\Core\Identifiable $model
     * @param array $newAttributes
     *
     * @return bool true if it was actually saved, false if not. Look above!
     **/
    public function update(Identifiable $model, array $newAttributes)
    {
        if ($result =$this->parent->update($model, $newAttributes)) {
            $this->cache->put($model);
        }

        return $result;
    }

    /**
     * @inheritdoc
     *
     * @param \Ems\Contracts\Core\Identifiable $model
     *
     * @return bool if the model was actually saved
     **/
    public function save(Identifiable $model)
    {
        if (!$result = $this->parent->save($model)) {
            return $result;
        }

        // Delete all cache entries related to the model
        $this->cache->forget($model);

        // Just put it into the cache again
        $this->cache->put($model);

        return $result;
    }

    /**
     * @inheritdoc
     *
     * @param \Ems\Contracts\Core\Identifiable $model
     *
     * @return bool
     **/
    public function delete(Identifiable $model)
    {
        if (!$result = $this->parent->delete($model)) {
            return $result;
        }

        $this->cache->forget($model);

        return $result;
    }

    /**
     * Return a prototype for the objects the parent repository creates.
     *
     * @return Identifiable
     */
    protected function prototype()
    {
        if (!$this->prototype) {
            $this->prototype = $this->make();
        }
        return $this->prototype;
    }


}