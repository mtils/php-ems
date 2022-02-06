<?php
/**
 *  * Created by mtils on 02.02.2022 at 21:11.
 **/

namespace Ems\Contracts\Core;

/**
 * This is a string object. In the future it will work in oo string syntax. For
 * now it acts as a generic renderable.
 */
class Str implements Renderable
{
    use StringableTrait;

    /**
     * @var string
     */
    protected $mimeType = 'text/plain';

    /**
     * @var string
     */
    protected $raw = '';

    /**
     * @var Renderer
     */
    protected $renderer;

    /**
     * Create a new Str.
     *
     * @param string $raw
     * @param string $mimeType
     */
    public function __construct(string $raw='', string $mimeType='text/plain')
    {
        $this->raw = $raw;
        $this->mimeType = $mimeType;
    }

    /**
     * @return string
     */
    public function getRaw(): string
    {
        return $this->raw;
    }

    /**
     * @param string $raw
     * @return Str
     */
    public function setRaw(string $raw): Str
    {
        $this->raw = $raw;
        return $this;
    }

    /**
     * @return string
     */
    public function mimeType() : string
    {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     * @return $this
     */
    public function setMimeType(string $mimeType) : Str
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     * @return Renderer|null
     */
    public function getRenderer() : ?Renderer
    {
        return $this->renderer;
    }

    /**
     * @param Renderer $renderer
     * @return $this
     */
    public function setRenderer(Renderer $renderer) : Str
    {
        $this->renderer = $renderer;
        return $this;
    }

    /**
     * @return Stringable|string
     */
    public function toString()
    {
        if ($this->renderer) {
            return $this->renderer->render($this);
        }
        return $this->raw;
    }

}