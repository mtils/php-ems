<?php

namespace Ems\Contracts\Core;

use Countable;
use IteratorAggregate;

/**
 * AsciiContent is Content for text files. It can return the text in lines
 **/
interface AsciiContent extends Content
{
    /**
     * Return an ContentIterator which allows to iterate over the lines
     *
     * @return ContentIterator
     **/
    public function lines();

}
