<?php

namespace Ems\Graphics;

use ArrayIterator;
use Ems\Contracts\Graphics\LayoutItem;

/**
 * @see Ems\Contracts\Graphics\Layout
 **/
trait LayoutTrait
{
    /**
     * @var array
     **/
    protected $_layout = [];

    /**
     * @var int
     **/
    protected $_maxColumns = 0;

    /**
     * @var int
     **/
    protected $_maxRows = 0;

    /**
     * Returns the amount of columns.
     *
     * @return int
     **/
    public function columnCount()
    {
        $maxCol = 0;

        foreach ($this->_layout as $row => $items) {
            foreach ($items as $column => $item) {
                $maxCol = max($column, $maxCol);
            }
        }

        return $maxCol;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     **/
    public function rowCount()
    {
        return count($this->_layout);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Ems\Contracts\Graphics\LayoutItem
     **/
    public function getItem($row, $column)
    {
        if (isset($this->_layout[$row][$column])) {
            return $this->_layout[$row][$column];
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param int                                $row
     * @param int                                $column
     * @param \Ems\Contracts\Graphics\LayoutItem $item
     *
     * @return self
     **/
    public function setItem($row, $column, LayoutItem $item)
    {
        if ($this->rowExceedsMax($row)) {
            throw new OutOfBoundsException('You cannot add more than '.$this->getMaxRows().' rows');
        }

        if ($this->columnExceedsMax($column)) {
            throw new OutOfBoundsException('You cannot add more than '.$this->getMaxColumns().' columns');
        }

        $rowCount = $this->rowCount();
        $row = $row > $rowCount ? $rowCount : $row;

        if (!isset($this->_layout[$row])) {
            $this->_layout[$row] = [];
        }
        $this->_layout[$row][$column] = $item;

        $item->setRow($row);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $row
     * @param int $column
     *
     * @return self
     **/
    public function remove($row, $column)
    {
        if (isset($this->_layout[$row][$column])) {
            unset($this->_layout[$row][$column]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Graphics\LayoutItem $item
     *
     * @return self
     **/
    public function removeItem(LayoutItem $item)
    {
        $itemId = spl_object_hash($item);

        foreach ($this as $myItem) {
            if ($itemId == spl_object_hash($myItem)) {
                return $this->remove($myItem->getRow(), $myItem->getColumn());
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     **/
    public function getMaxColumns()
    {
        return $this->_maxColumns;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $maxColumns
     *
     * @return self
     **/
    public function setMaxColumns($maxColumns)
    {
        $this->_maxColumns = $maxColumns;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     **/
    public function getMaxRows()
    {
        return $this->_maxRows;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $maxRows
     *
     * @return self
     **/
    public function setMaxRows($maxRows)
    {
        $this->_maxRows = $maxRows;

        return $this;
    }

    /**
     * Return the amount of items.
     *
     * @return int
     **/
    public function count()
    {
        return count($this->collectItems());
    }

    /**
     * Return an iterator for all items.
     *
     * @return \ArrayIterator
     **/
    public function getIterator()
    {
        return new ArrayIterator($this->collectItems());
    }

    /**
     * Collects all items.
     *
     * @return array
     **/
    protected function collectItems()
    {
        $items = [];

        foreach ($this->_layout as $row => $unused) {
            foreach ($this->_layout[$row] as $column => $item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    protected function rowExceedsMax($row)
    {
        if ($this->getMaxRows() == 0) {
            return false;
        }

        return $row >= $this->getMaxRows();
    }

    protected function columnExceedsMax($column)
    {
        if ($this->getMaxColumns() == 0) {
            return false;
        }

        return $column >= $this->getMaxColumns();
    }
}
