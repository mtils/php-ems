<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\AsciiContent as AsciiContentContract;
use Iterator;

class AsciiContent extends BinaryContent implements AsciiContentContract
{
    /**
     * {@inheritdoc}
     *
     * @return Iterator|string[]
     **/
    public function lines()
    {
        return new LineReadIterator($this->url(), $this->getStream());
    }
}
