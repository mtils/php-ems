<?php


namespace Ems\Contracts\Graphics;


interface LayoutItem
{
    /**
     * Returns the row inside its layout
     *
     * @return int
     **/
    public function getRow();

    /**
     * Set the row inside its layout
     *
     * @param int $row
     * @return self
     **/
    public function setRow($row);

    /**
     * Return the column inside its layout
     *
     * @return int
     **/
    public function getColumn();

    /**
     * Set the column inside its layout
     *
     * @param int $column
     * @return self
     **/
    public function setColumn($column);

    /**
     * Get the layout this item is attached to
     *
     * @return \Ems\Contracts\Graphics\Layout
     **/
    public function getLayout();

    /**
     * Set the layout this item is attached to
     *
     * @param \Ems\Contracts\Graphics\Layout $layout
     * @return self
     **/
    public function setLayout(Layout $layout);

    /**
     * Return how many rows this item should span
     *
     * @return int
     **/
    public function getRowSpan();

    /**
     * Set how many rows this item should span
     *
     * @param int $rowSpan
     * @return int
     **/
    public function setRowSpan($rowSpan);

    /**
     * Return how many columns this item should span
     *
     * @return int
     **/
    public function getColumnSpan();

    /**
     * Set how many columns this item should span
     *
     * @param int $columnSpan
     * @return int
     **/
    public function setColumnSpan($columnSpan);
}