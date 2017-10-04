<?php


namespace Ems\Foundation;

use Ems\Contracts\Foundation\InputProcessor as InputProcessorContract;
use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\NamedCallableChain as ChainContract;
use Ems\Core\NamedObject;


class InputProcessorTest extends \Ems\TestCase
{
        public function test_implements_interface()
    {
        $this->assertInstanceOf(InputProcessorContract::class, $this->newSample());
        $this->assertInstanceOf(ChainContract::class, $this->newSample());
    }

    public function test_setChain_sets_casters()
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


    public function test_chain_parameters_are_used()
    {
        $caster = $this->newSample();

        $extensions = [
            'convert_encoding' => function ($input, $encoding, $internalEncoding='utf-8') {
                                    $input['encoding'] = $encoding;
                                    return $input;
                                  },
            'trim'             => function ($input, $resource=null) {
                                    $input['resource'] = $resource;
                                    return $input;
                                  },
            'xtype_adjust'     => function ($input, $resource=null, $locale=null) {
                                    $input['locale'] = $locale;
                                    return $input;
                                  },
        ];

        foreach ($extensions as $name=>$callable) {
            $caster->extend($name, $callable);
        }

        $input = ['foo'=> 'bar'];

        $awaited = [
            'foo'     => 'bar',
            'encoding'=> 'iso-8859-1',
            'resource'=> new NamedObject,
            'locale'=> 'cz'
        ];

        $caster->setChain('trim|convert_encoding:iso-8859-1|xtype_adjust');

        $this->assertEquals($awaited, $caster->process($input, $awaited['resource'], $awaited['locale']));

    }

    public function test_chain_parameters_are_replaced()
    {
        $caster = $this->newSample();

        $extensions = [
            'convert_encoding' => function ($input, $encoding, $internalEncoding='utf-8') {
                                    $input['encoding'] = $encoding;
                                    return $input;
                                  },
            'trim'             => function ($input, $resource=null) {
                                    $input['resource'] = $resource;
                                    return $input;
                                  },
            'xtype_adjust'     => function ($input, $resource=null, $locale=null) {
                                    $input['locale'] = $locale;
                                    return $input;
                                  },
        ];

        foreach ($extensions as $name=>$callable) {
            $caster->extend($name, $callable);
        }

        $input = ['foo'=> 'bar'];

        $awaited = [
            'foo'     => 'bar',
            'encoding'=> 'utf-16',
            'resource'=> new NamedObject,
            'locale'=> 'cz'
        ];

        $caster->setChain('trim|convert_encoding:iso-8859-1|xtype_adjust');

        $caster = $caster->with('trim|convert_encoding:utf-16|xtype_adjust');

        $this->assertEquals($awaited, $caster->process($input, $awaited['resource'], $awaited['locale']));

    }

    protected function trigger($caster, array $input, AppliesToResource $r=null)
    {
        return $caster->process($input, $r);
    }

    protected function newSample()
    {
        return new InputProcessor();
    }
}
