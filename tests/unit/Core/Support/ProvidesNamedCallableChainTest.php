<?php


namespace Ems\Core\Support;


use Ems\Contracts\Core\NamedCallableChain;


class ProvidesNamedCallableChainTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(NamedCallableChain::class, $this->newChain());
    }

    public function test_setChain_sets_casters()
    {
        $chain = $this->newChain();
        $chain->extend('test1', function ($input) { $input['test1'] = true; return $input; });
        $chain->extend('test2', function ($input) { $input['test2'] = true; return $input; });
        $chain->extend('test3', function ($input) { $input['test3'] = true; return $input; });

        $input = [];

        $awaited = [
            'test1'=> true,
            'test2'=> true,
            'test3'=> true
        ];

        $chain->setChain(array_keys($awaited));

        $this->assertEquals($awaited, $this->trigger($chain, $input));

        $chain->setChain(implode('|', array_keys($awaited)));

        $this->assertEquals($awaited, $this->trigger($chain, []));
    }

    public function test_getChain_returns_readable_chain()
    {
        $chain = $this->newChain();
        $chain->extend('test1', function ($input) { $input['test1'] = true; return $input; });
        $chain->extend('test2', function ($input) { $input['test2'] = true; return $input; });
        $chain->extend('test3', function ($input) { $input['test3'] = true; return $input; });
        $this->assertEquals([], $chain->getChain());

        $this->assertEquals(['test1'], $chain->with('test1')->getChain());
    }

    public function test_with_sets_casters()
    {
        $chain = $this->newChain();
        $chain->extend('test1', function ($input) { $input['test1'] = true; return $input; });
        $chain->extend('test2', function ($input) { $input['test2'] = true; return $input; });
        $chain->extend('test3', function ($input) { $input['test3'] = true; return $input; });

        $input = [];

        $awaited = [
            'test1'=> true,
            'test2'=> true,
            'test3'=> true
        ];

        $chains = array_keys($awaited);

        $this->assertEquals($awaited, $this->trigger($chain->with($chains), $input));
    }

    public function test_with_merges_casters()
    {
        $chain = $this->newChain();
        $chain->extend('test1', function ($input) { $input['test1'] = true; return $input; });
        $chain->extend('test2', function ($input) { $input['test2'] = true; return $input; });
        $chain->extend('test3', function ($input) { $input['test3'] = true; return $input; });

        $input = [];

        $awaited = [
            'test1'=> true,
            'test3'=> true,
            'test2'=> true,
        ];

        $chain->setChain('test1', 'test3');

        $this->assertEquals($awaited, $this->trigger($chain->with('test2'), $input));
    }

    public function test_with_merges_casters_with_excludes()
    {
        $chain = $this->newChain();
        $chain->extend('test1', function ($input) { $input['test1'] = true; return $input; });
        $chain->extend('test2', function ($input) { $input['test2'] = true; return $input; });
        $chain->extend('test3', function ($input) { $input['test3'] = true; return $input; });

        $input = [];

        $awaited = [
            'test3'=> true,
        ];

        $chain->setChain('test1', 'test3');

        $this->assertEquals($awaited, $this->trigger($chain->with('!test1'), $input));
    }

    public function test_fork_extend_works_on_parent()
    {
        $chain = $this->newChain();
        $chain->extend('test1', function ($input) { $input['test1'] = true; return $input; });
        $chain->extend('test2', function ($input) { $input['test2'] = true; return $input; });
        $chain->extend('test3', function ($input) { $input['test3'] = true; return $input; });

        $input = [];

        $awaited = [
            'test1'=> true,
            'test3'=> true,
            'test4'=> true
        ];

        $chain->setChain('test1', 'test3');

        $fork = $chain->with('test1', 'test4');

        $fork->extend('test4', function ($input) { $input['test4'] = true; return $input; });

        $this->assertEquals($awaited, $this->trigger($fork, $input));
        $this->assertTrue($fork->hasExtension('test3'));
        $this->assertTrue($fork->hasExtension('test4'));
        $this->assertTrue($chain->hasExtension('test4'));
        $this->assertSame($chain, $fork->getParent());
        $this->assertCount(2, $chain->getChain());
        $this->assertCount(3, $fork->getChain());
    }

    public function test_with_on_fork_works()
    {
        $chain = $this->newChain();
        $chain->extend('test1', function ($input) { $input['test1'] = true; return $input; });

        $chain2 = $chain->with('test1');

    }

    protected function trigger($chain, array $input, array $metadata=[], $r=null)
    {
        return $chain->run($input, $metadata, $r);
    }

    protected function newChain()
    {
        return new NamedCallableChainTest();
    }
}

class NamedCallableChainTest implements NamedCallableChain
{
    use ProvidesNamedCallableChain;

    public function run(array $first, $resource=null)
    {
        $corrected = $first;

        foreach ($this->buildChain() as $name => $parameters) {
            $corrector = $this->getExtension($name);
            $result = $corrector($corrected, $resource);
            $corrected = &$result;
        }

        return $corrected;
    }
}
