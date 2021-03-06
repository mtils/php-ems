<?php

namespace Ems\Core;

use Ems\Contracts\Core\InputCaster as CasterContract;
use Ems\Contracts\Core\NamedCallableChain as ChainContract;

class InputCasterTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(CasterContract::class, $this->newSample());
        $this->assertInstanceOf(ChainContract::class, $this->newSample());
    }

    public function test_setCHain_sets_casters()
    {
        $caster = $this->newSample();
        $caster->extend('test1', function ($input) { $input['test1'] = true; return $input; });
        $caster->extend('test2', function ($input) { $input['test2'] = true; return $input; });
        $caster->extend('test3', function ($input) { $input['test3'] = true; return $input; });

        $input = [];

        $awaited = [
            'test1'=> true,
            'test2'=> true,
            'test3'=> true
        ];

        $caster->setChain(array_keys($awaited));

        $this->assertEquals($awaited, $this->trigger($caster, $input));

        $caster->setChain(implode('|', array_keys($awaited)));

        $this->assertEquals($awaited, $this->trigger($caster, []));
    }

    public function test_with_sets_casters()
    {
        $caster = $this->newSample();
        $caster->extend('test1', function ($input) { $input['test1'] = true; return $input; });
        $caster->extend('test2', function ($input) { $input['test2'] = true; return $input; });
        $caster->extend('test3', function ($input) { $input['test3'] = true; return $input; });

        $input = [];

        $awaited = [
            'test1'=> true,
            'test2'=> true,
            'test3'=> true
        ];

        $casters = array_keys($awaited);

        $this->assertEquals($awaited, $this->trigger($caster->with($casters), $input));
    }

    public function test_with_merges_casters()
    {
        $caster = $this->newSample();
        $caster->extend('test1', function ($input) { $input['test1'] = true; return $input; });
        $caster->extend('test2', function ($input) { $input['test2'] = true; return $input; });
        $caster->extend('test3', function ($input) { $input['test3'] = true; return $input; });

        $input = [];

        $awaited = [
            'test1'=> true,
            'test3'=> true,
            'test2'=> true,
        ];

        $caster->setChain('test1', 'test3');

        $this->assertEquals($awaited, $this->trigger($caster->with('test2'), $input));
    }

    public function test_with_merges_casters_with_excludes()
    {
        $caster = $this->newSample();
        $caster->extend('test1', function ($input) { $input['test1'] = true; return $input; });
        $caster->extend('test2', function ($input) { $input['test2'] = true; return $input; });
        $caster->extend('test3', function ($input) { $input['test3'] = true; return $input; });

        $input = [];

        $awaited = [
            'test3'=> true,
        ];

        $caster->setChain('test1', 'test3');

        $this->assertEquals($awaited, $this->trigger($caster->with('!test1'), $input));
    }

    public function test_fork_extend_works_on_parent()
    {
        $caster = $this->newSample();
        $caster->extend('test1', function ($input) { $input['test1'] = true; return $input; });
        $caster->extend('test2', function ($input) { $input['test2'] = true; return $input; });
        $caster->extend('test3', function ($input) { $input['test3'] = true; return $input; });

        $input = [];

        $awaited = [
            'test1'=> true,
            'test3'=> true,
            'test4'=> true
        ];

        $caster->setChain('test1', 'test3');

        $fork = $caster->with('test1', 'test4');

        $fork->extend('test4', function ($input) { $input['test4'] = true; return $input; });

        $this->assertEquals($awaited, $this->trigger($fork, $input));
        $this->assertTrue($fork->hasExtension('test3'));
        $this->assertTrue($fork->hasExtension('test4'));
        $this->assertTrue($caster->hasExtension('test4'));
        $this->assertSame($caster, $fork->getParent());
        $this->assertCount(2, $caster->getChain());
        $this->assertCount(3, $fork->getChain());
    }

    protected function trigger($caster, array $input, array $metadata=[], $r=null)
    {
        return $caster->cast($input, $metadata, $r);
    }

    protected function newSample()
    {
        return new InputCaster();
    }
}
