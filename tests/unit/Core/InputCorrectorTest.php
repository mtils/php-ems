<?php

namespace Ems\Core;

use Ems\Contracts\Core\InputCorrector as CorrectorContract;
use Ems\Contracts\Core\NamedCallableChain as ChainContract;

require_once __DIR__.'/InputCasterTest.php';

class InputCorrectorTest extends InputCasterTest
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(CorrectorContract::class, $this->newSample());
        $this->assertInstanceOf(ChainContract::class, $this->newSample());
    }

    protected function trigger($caster, array $input, array $metadata=[], $r=null)
    {
        return $caster->correct($input, $metadata, $r);
    }

    protected function newSample()
    {
        return new InputCorrector();
    }
}
