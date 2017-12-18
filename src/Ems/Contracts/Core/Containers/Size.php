<?php
/**
 *  * Created by mtils on 16.12.17 at 21:17.
 **/

namespace Ems\Contracts\Core\Containers;


use Ems\Contracts\Core\Type;
use Traversable;
use UnderflowException;
use function iterator_to_array;


class Size extends Pair
{

    /**
     * Size constructor.
     * @param float|Size $width
     * @param float      $height (optional)
     */
    public function __construct($width = null, $height = null)
    {
        parent::__construct($width, $height, 'float');
    }

    /**
     * @return float
     */
    public function width()
    {
        return $this->first;
    }

    /**
     * @param float $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        parent::setFirst($width);
        return $this;
    }

    /**
     * @return float
     */
    public function height()
    {
        return $this->second;
    }

    /**
     * @param float $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        parent::setSecond($height);
        return $this;
    }

    /**
     * Return the aspect ratio as a float.
     *
     * @return float
     */
    public function aspectRatio()
    {
        return abs($this->first) / abs($this->second);
    }

    /**
     * Return true if orientation is in landscape.
     *
     * @return string
     */
    public function isLandscape()
    {
        if (!$this->isValid()) {
            return false;
        }
        return $this->first > $this->second;
    }

    /**
     * Return if orientation is in portrait mode.
     *
     * @return bool
     */
    public function isPortrait()
    {
        if (!$this->isValid()) {
            return false;
        }
        return $this->second > $this->first;
    }

    /**
     * Return if width and height are equal.
     *
     * @return bool
     */
    public function isSquare()
    {
        if (!$this->isValid()) {
            return false;
        }
        return round($this->first) == round($this->second);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->first > 0 && $this->second > 0;
    }

    /**
     * Return width * height.
     *
     * @return float
     */
    public function total()
    {
        return $this->area();
    }

    /**
     * Return width * height.
     *
     * @return float
     */
    public function area()
    {
        return $this->first * $this->second;
    }

    /**
     * Scale the size by $factor.
     *
     * @param float|Size $factor
     *
     * @return static
     */
    public function scale($factor)
    {
        if ($factor instanceof Size) {
            return $this->scaleTo($factor);
        }
        return new static($this->first * $factor, $this->second * $factor);
    }

    /**
     * Scale the size to new dimensions. Left out either width
     * or height to keep the aspect ratio.
     * Returns a new Size.
     *
     * @param float|Size $width (optional)
     * @param float      $height (optional)
     *
     * @return static
     */
    public function scaleTo($width=null, $height=null)
    {
        if (!$width && !$height) {
            throw new UnderflowException('You have to pass at least width or height.');
        }

        if ($width instanceof Size) {
            return new static($width->width(), $width->height());
        }

        if ($width && $height) {
            return new static($width, $height);
        }

        if (!$width) {
            return new static($height * $this->aspectRatio(), $height);
        }

        return new static($width, $width / $this->aspectRatio());

    }

    /**
     * Scale the size so that it fits in $width and $height by its
     * biggest dimensions and keep the aspect ratio.
     * Returns a new Size.
     *
     * @param float|Size $width
     * @param float $height (optional)
     *
     * @return static
     */
    public function fitInto($width, $height=null)
    {

        $size = $width instanceof Size ? $width : new static($width, $height);

        if (!$size->isValid()) {
            return new static($size);
        }

        $myAspectRatio = $this->aspectRatio();

        $newWidth = $size->height() * $myAspectRatio;

        if ($newWidth <= $size->width()) {
            return new static($newWidth, $size->height());
        }

        return new static($size->width(), $size->width() / $myAspectRatio);
    }

    /**
     * Scale the size so that by keeping the aspect ratio it will be bigger then
     * the passed size. But this in the smallest possible size.
     * Returns a new Size.
     *
     * @param float|Size $width
     * @param float      $height (optional)
     *
     * @return static
     */
    public function expandTo($width, $height=null)
    {
        $size = $width instanceof Size ? $width : new static($width, $height);

        if (!$size->isValid()) {
            return $this->scaleTo($size);
        }

        $myAspectRatio = $this->aspectRatio();

        $newWidth = $size->height() * $myAspectRatio;

        if ($newWidth >= $size->width()) {
            return new static($newWidth, $size->height());
        }

        return new static($size->width(), $size->width() / $myAspectRatio);
    }

    /**
     * Multiplies the width with another width and this height with another height.
     * Returns a new Size.
     *
     * @param float|Size $width
     * @param float      $height (optional)
     *
     * @return static
     */
    public function multiply($width, $height=null)
    {
        if ($width instanceof Size) {
            $height = $width->height();
            $width = $width->width();
        }

        $height = $height ? $height : $width;

        return new static($this->width()*$width, $this->height()*$height);
    }

    /**
     * Adds (+) the width of another width and this height to another height.
     * Returns a new Size.
     *
     * @param float|Size $width
     * @param float      $height (optional)
     *
     * @return static
     */
    public function add($width, $height=null)
    {
        if ($width instanceof Size) {
            $height = $width->height();
            $width = $width->width();
        }

        $height = $height ? $height : $width;

        return new static($this->width()+$width, $this->height()+$height);
    }

    /**
     * Subtracts (-) the width of another width and another height from this height.
     * Returns a new Size.
     *
     * @param float|Size $width
     * @param float      $height (optional)
     *
     * @return static
     */
    public function subtract($width, $height=null)
    {
        if ($width instanceof Size) {
            $height = $width->height();
            $width = $width->width();
        }

        $height = $height ? $height : $width;

        return new static($this->width()-$width, $this->height()-$height);
    }

    /**
     * Divides (/) the width of another width and this height of another height.
     * Returns a new Size.
     *
     * @param float|Size $width
     * @param float      $height (optional)
     *
     * @return static
     */
    public function divide($width, $height=null)
    {
        if ($width instanceof Size) {
            $height = $width->height();
            $width = $width->width();
        }

        $height = $height ? $height : $width;

        return new static($this->width()/$width, $this->height()/$height);
    }

    /**
     * Find the best matching size in the passed $sizes.
     * Use this if you have a "target size" and you want to find the best fitting
     * size of the passed sizes.
     * It just chooses the smallest size which is bigger than this one or the
     * biggest size if all are smaller than this one.
     * Currently it ignores the aspect ratio.
     *
     * @param Size[] $sizes
     *
     * @return Size
     */
    public function findBest($sizes)
    {
        $sizes = $sizes instanceof Traversable ? iterator_to_array($sizes) : array_values($sizes);

        $count = count($sizes);

        if ($count == 0) {
            throw new UnderflowException('No sizes passed to find a matching size');
        }

        if ($count == 1) {
            return $sizes[0];
        }

        $bigger = [];
        $biggest = new Size(0,0);

        foreach ($sizes as $size) {

            if ($size->equals($this)) {
                return $size;
            }

            if ($size->isGreaterThan($biggest)) {
                $biggest = $size;
            }

            if ($size->isGreaterThan($this)) {
                $bigger[] = $size;
            }
        }

        if (!$bigger) {
            return $biggest;
        }

        $smallest = clone $biggest;

        /** @var Size $size */
        foreach ($bigger as $size) {
            if ($size->isLessThan($smallest)) {
                $smallest = $size;
            }
        }

        return $smallest;

    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "{$this->first}x{$this->second}";
    }

    /**
     * @param mixed $value
     *
     * @return float
     */
    protected function checkType($value)
    {
        if ($value === null) {
            return $value;
        }

        Type::force($value, 'numeric', true);
        return $value === null ? $value : (float)$value;
    }
}