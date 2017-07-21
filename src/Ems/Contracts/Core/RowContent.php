<?php

namespace Ems\Contracts\Core;

use Countable;
use IteratorAggregate;

/**
 * RowContent is Content for row based text files like csv files.
 * It can also be used for excel files or xml or json files which can
 * be represented in rows.
 * It does intentionally not extend AsciiContent. XLS is no Ascii and in XML
 * or json the lines could not map to lines in any way.
 **/
interface RowContent extends Content
{
    /**
     * Return an ContentIterator which allows to iterate over the rows
     *
     * @param mixed $layer (optional) Allow to choose a spreadsheet or tag or so
     *
     * @return ContentIterator
     **/
    public function rows($layer=null);

}
