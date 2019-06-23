<?php
/**
 *  * Created by mtils on 22.06.19 at 09:02.
 **/

namespace Ems\Core\Support;


use Ems\Contracts\Core\Content;
use Ems\Contracts\Core\Input as InputContract;
use Ems\Core\Exceptions\UnsupportedParameterException;
use function method_exists;
use function spl_object_hash;

trait InputTrait
{
    /**
     * @var string
     */
    protected $locale = 'en_EN';

    /**
     * @var Content
     */
    protected $content;

    /**
     * @var InputContract
     */
    protected $previous;

    /**
     * @var InputContract
     */
    protected $next;

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function locale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return Content
     */
    public function content()
    {
        return $this->content;
    }

    /**
     * @param Content $content
     *
     * @return $this
     */
    public function setContent(Content $content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $pool
     *
     * @return self
     */
    public function only($pool)
    {
        if ($pool !== static::POOL_CUSTOM) {
            throw new UnsupportedParameterException('The base input class only supports custom attributes.');
        }
        return clone $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return InputContract|null
     */
    public function previous()
    {
        return $this->previous;
    }

    /**
     * @param InputContract $previous
     * @return $this
     */
    public function setPrevious(InputContract $previous)
    {
        $this->previous = $previous;
        if (!method_exists($previous, 'setNext')) {
            return $this;
        }

        if (!$next = $previous->next()) {
            $previous->setNext($this);
            return $this;
        }

        if (!$this->isSame($next)) {
            $previous->setNext($this);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return InputContract|null
     */
    public function next()
    {
        return $this->next;
    }

    /**
     * @param InputContract $next
     *
     * @return InputTrait
     */
    public function setNext(InputContract $next)
    {
        $this->next = $next;

        if (!method_exists($next, 'setPrevious')) {
            return $this;
        }

        if (!$previous = $next->previous()) {
            $next->setPrevious($this);
            return $this;
        }

        if (!$this->isSame($previous)) {
            $next->setPrevious($this);
        }
        return $this;
    }

    /**
     * @param InputContract $other
     *
     * @return bool
     */
    protected function isSame(InputContract $other)
    {
        return spl_object_hash($other) === spl_object_hash($this);
    }

}