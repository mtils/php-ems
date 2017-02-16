<?php

namespace Ems\XType;

use Ems\Contracts\XType\XType;
use Ems\Contracts\XType\TypeFactory as TypeFactoryContract;

class TypeFactoryChainTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            TypeFactoryContract::class,
            $this->newChain()
        );
    }

    public function test_canCreate_returns_true_if_other_also()
    {
        $factory = $this->mockFactory();
        $chain = $this->newChain($factory);

        $config = 'foo';

        $factory->shouldReceive('canCreate')
                ->with($config)
                ->andReturn(true);

        $this->assertTrue($chain->canCreate($config));
    }

    public function test_canCreate_returns_false_if_other_also()
    {
        $factory = $this->mockFactory();
        $chain = $this->newChain($factory);

        $config = 'foo';

        $factory->shouldReceive('canCreate')
                ->with($config)
                ->andReturn(false);

        $this->assertFalse($chain->canCreate($config));
    }

    public function test_toType_returns_result_of_other()
    {
        $factory = $this->mockFactory();
        $chain = $this->newChain($factory);

        $config = 'foo';

        $factory->shouldReceive('canCreate')
                ->with($config)
                ->andReturn(true);

        $factory->shouldReceive('toType')
                ->with($config)
                ->andReturn('result');

        $this->assertEquals('result', $chain->toType($config));
    }

    protected function newChain(TypeFactoryContract $addFactory=null)
    {
        $chain = new TypeFactoryChain;
        return $chain->add($addFactory ?: $this->mockFactory());

    }


    protected function mockFactory()
    {
        return $this->mock(TypeFactoryContract::class);
    }

}
