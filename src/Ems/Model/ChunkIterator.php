<?php
/**
 *  * Created by mtils on 25.07.20 at 08:55.
 **/

namespace Ems\Model;


use Iterator;

use Traversable;

use function call_user_func;
use function is_array;
use function iterator_to_array;

/**
 * Class ChunkIterator
 *
 * This is a small tool iterator to query some storage/backend in chunks.
 * It does not know a length or how many results will come. It just passes a
 * $page and a $perPage (or $offset, $limit if you want) to a callable until it
 * returns less than $offset results. (No result is also less than $offset ;-) )
 *
 * @package Ems\Model
 */
class ChunkIterator implements Iterator
{
    /**
     * @var int
     */
    private $chunkSize = 1000;

    /**
     * @var int
     */
    private $offset = 0;

    /**
     * @var int
     */
    private $pageOffset = 0;

    /**
     * @var callable
     */
    private $paginatable;

    /**
     * @var array
     */
    private $chunk;

    /**
     * @var bool
     */
    private $finished = false;

    /**
     * ChunkIterator constructor.
     *
     * @param callable $paginatable
     * @param int $chunkSize
     */
    public function __construct(callable $paginatable, $chunkSize=1000)
    {
        $this->paginatable = $paginatable;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Rewind the Iterator to the first element
     * @link https://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->offset = 0;
        $this->pageOffset = 0;
        $this->finished = false;
        $this->chunk = $this->read($this->offset, $this->chunkSize);
    }

    /**
     * Checks if current position is valid
     * @link https://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        if ($this->finished) {
            return false;
        }
        return isset($this->chunk[$this->pageOffset]);

    }

    /**
     * Move forward to next element
     * @link https://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        ++$this->offset;
        ++$this->pageOffset;
        if (isset($this->chunk[$this->pageOffset]) || $this->finished) {
            return;
        }

        // If the offset does not exist and the pageOffset is less than
        // the chunkSize the last read result has to be smaller than the chunkSize
        // and must be finished
        if ($this->pageOffset < $this->chunkSize) {
            $this->finished = true;
            return;
        }

        $this->chunk = $this->read($this->offset, $this->chunkSize);
        $this->pageOffset = 0;
    }

    /**
     * Return the current element
     * @link https://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->chunk[$this->pageOffset];
    }

    /**
     * Return the key of the current element
     * @link https://php.net/manual/en/iterator.key.php
     * @return string|float|int|bool|null scalar on success, or null on failure.
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @param int $count
     *
     * @return array|null
     */
    private function read($offset, $count)
    {
        if(!$result = call_user_func($this->paginatable, $offset, $count)) {
            $this->finished = true;
            return null;
        }
        return is_array($result) ? $result : iterator_to_array($result, false);
    }

}