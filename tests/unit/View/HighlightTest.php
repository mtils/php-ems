<?php

namespace Ems\View;

use Ems\Contracts\View\Highlight as HighlightContract;

class HighlightTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(HighlightContract::class, $this->newHighlight());
    }

//     public function test_limit

    protected function newHighlight()
    {
        return new Highlight();
    }
}
