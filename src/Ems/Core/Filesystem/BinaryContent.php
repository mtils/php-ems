<?php

namespace Ems\Core\Filesystem;

use Countable;
use Ems\Contracts\Core\Content;
use Ems\Contracts\Core\Stream;
use Ems\Contracts\Core\Url;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Support\StringableTrait;
use Iterator;
use LogicException;


class BinaryContent implements Content
{
    use StringableTrait;

    /**
     * @var Stream
     */
    protected $stream;

    /**
     * @var Url
     **/
    protected $url;

    /**
     * @var string
     **/
    protected $mimeType;

    /**
     * @var callable
     **/
    protected $iteratorCreator;

    public function __construct(Stream $stream=null)
    {
        $this->stream = $stream;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function mimeType()
    {
        return $this->mimeType;
    }

    /**
     * Set the mimeType (once)
     *
     * @param string $mimeType
     *
     * @return self
     **/
    public function setMimeType($mimeType)
    {
        if ($this->mimeType && $mimeType != $this->mimeType) {
            throw new LogicException('You can only configure a content once');
        }
        $this->mimeType = $mimeType;
        return $this;

    }

    /**
     * {@inheritdoc}
     *
     * @return Url
     **/
    public function url()
    {
        if ($this->url) {
            return $this->url;
        }

        if ($this->stream) {
            return $this->stream->url();
        }

        return new \Ems\Core\Url();
    }

    /**
     * Set the url.
     *
     * @param string $url
     *
     * @return self
     **/
    public function setUrl($url)
    {
        $url = "$url"; // Cast Url objects to string

        if ($this->url && $url != $this->url) {
            throw new LogicException('You can only configure a content once');
        }

        $this->url = new \Ems\Core\Url($url);



        return $this;
    }

    /**
     * Return the size in bytes
     *
     * @return int
     **/
    public function count()
    {
        $iterator = $this->getIterator();

        if ($iterator instanceof Countable) {
            return count($iterator);
        }

        return strlen($this->toString());
    }

    /**
     * Return an iterator to iterate bytes by bytes
     *
     * @return Iterator
     **/
    public function getIterator()
    {
        if ($this->iteratorCreator) {
            return call_user_func($this->iteratorCreator, $this, $this->stream);
        }

        return $this->createIterator();
    }

    /**
     * Set a custom callable to create the iterator
     *
     * @param callable $creator
     *
     * @return self
     **/
    public function createIteratorBy(callable $creator)
    {
        $this->iteratorCreator = $creator;
        return $this;
    }

    /**
     * Renders the result. Is just inside its own method to allow easy
     * overwriting __toString().
     *
     * @return string
     **/
    public function toString()
    {
        return (string)$this->getStream();
    }

    /**
     * Return the used stream,
     *
     * @return Stream
     */
    public function getStream()
    {
        if ($this->stream) {
            return $this->stream;
        }

        if (!$this->url) {
            throw new UnConfiguredException('No stream, no url. No idea how to create a stream.');
        }

        return new FileStream($this->url());
    }

    /**
     * Create content from a string.
     *
     * @param string $string
     * @param string $mimeType (optional)
     *
     * @return static
     */
    public static function forString($string, $mimeType='')
    {
        $content = new static(new StringStream($string));
        if ($mimeType) {
            $content->setMimeType($mimeType);
        }
        return $content;
    }

    /**
     * Create the iterator
     *
     * @return Iterator
     **/
    protected function createIterator()
    {
        return $this->getStream();
    }
}
