<?php

namespace Ems\Contracts\Graphics;

use Countable;
use IteratorAggregate;

/**
 * A layout represents a (grid) layout. It can have items
 * which knows its positions.
 * The Countable::count() method is for total amount of
 * items in this layout
 * The IteratorAggregate::getIterator() returns an iterator
 * iterating over all items ordered by row, column.
 **/
interface Layout extends Countable, IteratorAggregate
{
    const VERTICAL = 3;

    const HORIZONTAL = 4;

    const GRID = 5;

    /**
     * Returns the amount of columns.
     *
     * @return int
     **/
    public function columnCount();

    /**
     * Returns the amount of rows.
     *
     * @return int
     **/
    public function rowCount();

    /**
     * Return the LayoutItem setted for $row and $column.
     *
     * @return \Ems\Contracts\Graphics\LayoutItem
     **/
    public function getItem($row, $column);

    /**
     * Set a LayoutItem for $row and $column.
     *
     * @param int                                $row
     * @param int                                $column
     * @param \Ems\Contracts\Graphics\LayoutItem $item
     *
     * @return self
     **/
    public function setItem($row, $column, LayoutItem $item);

    /**
     * Remove the item by position.
     *
     * @param int $row
     * @param int $column
     *
     * @return self
     **/
    public function remove($row, $column);

    /**
     * Remove an item by the item itself.
     *
     * @param \Ems\Contracts\Graphics\LayoutItem $item
     *
     * @return self
     **/
    public function removeItem(LayoutItem $item);

    /**
     * Return the maximal amount this layout can have
     * 0 is unbounded.
     *
     * @return int
     **/
    public function getMaxColumns();

    /**
     * Set the maximal amount of columns.
     *
     * @param int $maxColumns
     *
     * @return self
     **/
    public function setMaxColumns($maxColumns);

    /**
     * Return the maximal amount of rows this layout can have
     * 0 is unbounded.
     *
     * @return int
     **/
    public function getMaxRows();

    /**
     * Set the maximum amount of rows.
     *
     * @param int $maxRows
     *
     * @return self
     **/
    public function setMaxRows($maxRows);
}
