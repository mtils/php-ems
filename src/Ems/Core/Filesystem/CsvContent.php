<?php

namespace Ems\Core\Filesystem;

use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Contracts\Core\RowContent;

class CsvContent extends AsciiContent implements RowContent
{
    /**
     * {@inheritdoc}
     *
     * @param mixed $layer (optional) This is not supported by csv
     *
     * @return CsvReadIterator
     **/
    public function rows($layer=null)
    {
        if ($layer !== null) {
            throw new UnsupportedParameterException('Layers are not supported by csv');
        }

        return new CsvReadIterator($this->url(), $this->getStream());
    }

}
