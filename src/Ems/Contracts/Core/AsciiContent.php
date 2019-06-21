<?php

namespace Ems\Contracts\Core;

use Iterator;

/**
 * AsciiContent is Content for text files. It can return the text in lines
 **/
interface AsciiContent extends Content
{
    /**
     * Return an ContentIterator which allows to iterate over the lines
     *
     * @return Iterator|string[]
     **/
    public function lines();

}
