<?php
/**
 *  * Created by mtils on 06.01.18 at 05:28.
 **/

namespace Ems\Contracts\Pagination;


use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Contracts\Core\StringableTrait;
use const STR_PAD_LEFT;
use function str_pad;

/**
 * Class Page
 *
 * A Page is one entry in a paginator. Basically it contains
 * a number and an url. All the other methods are just helpers for
 * a cleaner wording when rendering it.
 *
 * @package Ems\Contracts\Pagination
 */
class Page implements Stringable
{
    use StringableTrait;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * Page constructor.
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * Return the page index inside pages (starting by 1).
     * Return 0 if the paginator is not LengthAware.
     *
     * @return int
     */
    public function number()
    {
        return $this->values['number'];
    }

    /**
     * Return the url to this page.
     *
     * @return UrlContract
     *
     * @throws UnConfiguredException
     */
    public function url()
    {
        if (isset($this->values['url']) && $this->values['url'] instanceof  UrlContract) {
            return $this->values['url'];
        }
        throw new UnConfiguredException('No url was given by the paginator. Did you peform Paginator::setBaseUrl()?');
    }

    /**
     * Return true if this page is the current page.
     *
     * @return bool
     */
    public function isCurrent()
    {
        return $this->values['is_current'];
    }

    /**
     * Return true if this page is the previous page of the current page.
     *
     * @return bool
     */
    public function isPrevious()
    {
        return $this->values['is_previous'];
    }

    /**
     * Return true if this page is the next page of the current page.
     *
     * @return bool
     */
    public function isNext()
    {
        return $this->values['is_next'];
    }

    /**
     * Return true if this is the first page.
     *
     * @return bool
     */
    public function isFirst()
    {
        return $this->values['is_first'];
    }

    /**
     * Return true if this is the last page.
     *
     * @return bool
     */
    public function isLast()
    {
        return $this->values['is_last'];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function toString()
    {
        return $this->isPlaceholder() ? '...' : (string)$this->number();
    }

    /**
     * Return the offset of a database query for that page.
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->values['offset'];
    }

    /**
     * Return true if this is just a placeholder and no real page. (... or so)
     *
     * @return bool
     */
    public function isPlaceholder()
    {
        return $this->number() < 1;
    }

    /**
     * Just a helper method for debugging.
     *
     * @return string
     */
    public function dump()
    {
        $isFirst = $this->dumpFlag('first', $this->isFirst());
        $isCurrent = $this->dumpFlag('current', $this->isCurrent());
        $isPrevious = $this->dumpFlag('prev', $this->isPrevious());
        $isNext = $this->dumpFlag('next', $this->isNext());
        $isLast = $this->dumpFlag('last', $this->isLast());

        $number = str_pad((string)$this->number(), 10, ' ', STR_PAD_LEFT);

        $flags = "$isCurrent|$isFirst|$isLast|$isPrevious|$isNext";

        $string = str_pad("'$this'", 10, ' ', STR_PAD_LEFT);

        $offset = str_pad((string)$this->getOffset(), 20, ' ', STR_PAD_LEFT);

        return "Page #$number $string offset:$offset flags:$flags";
    }

    protected function dumpFlag($name, $value)
    {
        return $value ? "$name+" : "$name-";
    }
}