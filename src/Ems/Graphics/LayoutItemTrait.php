<?php


namespace Ems\Graphics;


use Ems\Contracts\Graphics\Layout;

/**
 * @see \Ems\Contracts\Graphics\LayoutItem
 **/
trait LayoutItemTrait
{

    /**
     * @var int
     **/
    protected $_row = 0;

    /**
     * @var int
     **/
    protected $_column = 0;

    /**
     * @var \Ems\Contracts\Graphics\Layout
     **/
    protected $_layout;

    /**
     * @var int
     **/
    protected $_rowSpan = 1;

    /**
     * @var int
     **/
    protected $_columnSpan = 1;

    /**
     * {@inheritdoc}
     *
     * @return int
     **/
    public function getRow()
    {
        return $this->_row;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $row
     * @return self
     **/
    public function setRow($row)
    {
        $this->_row = $row;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     **/
    public function getColumn()
    {
        return $this->_column;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $column
     * @return self
     **/
    public function setColumn($column)
    {
        $this->_column = $column;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Ems\Contracts\Graphics\Layout
     **/
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Graphics\Layout $layout
     * @return self
     **/
    public function setLayout(Layout $layout)
    {
        $this->_layout = $layout;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     **/
    public function getRowSpan()
    {
        return $this->_rowSpan;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $rowSpan
     * @return int
     **/
    public function setRowSpan($rowSpan)
    {
        $this->_rowSpan = $rowSpan;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     **/
    public function getColumnSpan()
    {
        return $this->_columnSpan;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $columnSpan
     * @return int
     **/
    public function setColumnSpan($columnSpan)
    {
        $this->_columnSpan = $columnSpan;
        return $this;
    }

}