<?php
/**
 *  * Created by mtils on 18.07.20 at 17:34.
 **/

namespace Ems\Model\Database;


use ArrayIterator;
use Ems\Contracts\Model\Relationship;
use Exception;
use IteratorAggregate;
use Traversable;

class RelationMap implements IteratorAggregate
{
    /**
     * @var string
     */
    protected $ormClass = '';

    /**
     * @var array
     */
    protected $relations = [];

    public function __construct($ormClass='')
    {
        $this->ormClass = $ormClass;
    }

    /**
     * @return string
     */
    public function getOrmClass()
    {
        return $this->ormClass;
    }

    /**
     * @param string $ormClass
     * @return RelationMap
     */
    public function setOrmClass($ormClass)
    {
        $this->ormClass = $ormClass;
        return $this;
    }


    /**
     * @param string $name
     *
     * @return Relationship|null
     */
    public function relation($name)
    {
        if (($this->relations[$name]['relation'])) {
            return $this->relations[$name]['relation'];
        }
        return null;
    }

    /**
     * @param string $relationName
     * @return mixed|null
     */
    public function path($relationName)
    {
        if (($this->relations[$relationName]['path'])) {
            return $this->relations[$relationName]['path'];
        }
        return null;
    }

    /**
     * @param string       $name
     * @param Relationship $relation
     * @param string       $path
     */
    public function addRelation($name, Relationship $relation, $path='')
    {
        $this->relations[$name] = [
            'relation'  => $relation,
            'path'      => $path
        ];
    }

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @throws Exception on failure.
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        $relations = [];
        foreach($this->relations as $name=>$data) {
            $relations[$name] = $data['relation'];
        }
        return new ArrayIterator($relations);
    }


}