<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\AsciiContent as AsciiContentContract;

class AsciiContent extends BinaryContent implements AsciiContentContract
{
    /**
     * {@inheritdoc}
     *
     * @return ContentIterator
     **/
    public function lines()
    {
        return new LineReadIterator($this->url, $this->filesystem);
    }
}
