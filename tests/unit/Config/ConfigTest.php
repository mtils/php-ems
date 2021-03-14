<?php
/**
 *  * Created by mtils on 2/28/21 at 9:22 AM.
 **/

namespace unit\Config;


use ArrayAccess;
use Ems\Config\Config;
use Ems\Config\Processors\ConfigVariablesParser;
use Ems\TestCase;
use Ems\Testing\LoggingCallable;
use OverflowException;
use stdClass;
use Traversable;

use UnderflowException;

use UnexpectedValueException;

use function iterator_to_array;

class ConfigTest extends TestCase
{
    /**
     * @test
     */
    public function it_instantiates()
    {
        $config = $this->make();
        $this->assertInstanceOf(Config::class, $config);
        $this->assertInstanceOf(ArrayAccess::class, $config);
        $this->assertInstanceOf(Traversable::class, $config);
    }

    /**
     * @test
     */
    public function append_and_get_source()
    {
        $config = $this->make();
        $source = $this->source();
        $config->appendSource($source);
        $this->assertTrue($config->offsetExists('app'));
        $this->assertTrue($config->offsetExists('cache'));
        $this->assertFalse($config->offsetExists('bar'));

        $this->assertEquals('bar', $config['foo']);
        $this->assertEquals($source['app']['key'], $config['app']['key']);
    }

    /**
     * @test
     */
    public function offsetSet_changes_keys()
    {
        $config = $this->make();
        $source = $this->source();
        $config->appendSource($source);
        $this->assertEquals('bar', $config['foo']);
        $this->assertEquals($source['app']['key'], $config['app']['key']);
        $config['foo'] = 'baz';
        $this->assertEquals('baz', $config['foo']);

    }

    /**
     * @test
     */
    public function offsetUnset_removes_keys()
    {
        $config = $this->make();
        $source = $this->source();
        $config->appendSource($source);
        $this->assertTrue($config->offsetExists('app'));
        $this->assertTrue($config->offsetExists('cache'));
        $this->assertFalse($config->offsetExists('bar'));

        unset($config['app']);
        $this->assertFalse($config->offsetExists('app'));

    }

    /**
     * @test
     */
    public function iterate_over_config_works()
    {
        $config = $this->make();
        $source = $this->source();
        $copy = [];
        $config->appendSource($source);

        foreach ($config as $key=>$value) {
            $copy[$key] = $value;
        }
        $this->assertEquals($source, $copy);
    }

    /**
     * @test
     */
    public function append_multiple_sources()
    {
        $config = $this->make();
        $source = $this->source();
        $source2 = $this->source();
        $source2['foo'] = 'baz2';
        $config->appendSource($source);
        $config->appendSource($source2);

        $this->assertEquals($source2['foo'], $config['foo']);

    }

    /**
     * @test
     */
    public function prepend_multiple_sources()
    {
        $config = $this->make();
        $source = $this->source();
        $source2 = $this->source();
        $source2['foo'] = 'baz2';
        $config->appendSource($source);
        $config->prependSource($source2);

        $this->assertEquals($source['foo'], $config['foo']);

    }

    /**
     * @test
     */
    public function appendPostProcessor_hooks_processor()
    {
        $config = $this->make();
        $source = $this->source();
        $processor = new LoggingCallable(function (array $config) {
            $config['foo'] = 'test';
            return $config;
        });
        $config->appendSource($source);
        $config->appendPostProcessor($processor);

        $this->assertEquals('test', $config['foo']);
    }

    /**
     * @test
     */
    public function appendPostProcessor_hooks_multiple_processor()
    {
        $config = $this->make();
        $source = $this->source();
        $processor = new LoggingCallable(function (array $config) {
            $config['foo'] = 'test';
            return $config;
        });
        $processor2 = new LoggingCallable(function (array $config, array $originalConfig) {
            $this->assertEquals('bar', $originalConfig['foo']);
            $this->assertEquals('test', $config['foo']);
            $config['foo'] = 'test2';
            return $config;
        });
        $config->appendSource($source);
        $config->appendPostProcessor($processor);
        $config->appendPostProcessor($processor2);

        $this->assertEquals('test2', $config['foo']);
    }

    /**
     * @test
     */
    public function prependPostProcessor_hooks_multiple_processor()
    {
        $config = $this->make();
        $source = $this->source();
        $processor = new LoggingCallable(function (array $config, array $originalConfig) {
            $this->assertEquals('bar', $originalConfig['foo']);
            $this->assertEquals('test2', $config['foo']);
            $config['foo'] = 'test';
            return $config;
        });
        $processor2 = new LoggingCallable(function (array $config, array $originalConfig) {
            $this->assertEquals('bar', $originalConfig['foo']);
            $config['foo'] = 'test2';
            return $config;
        });
        $config->appendSource($source);
        $config->appendPostProcessor($processor);
        $config->prependPostProcessor($processor2);

        $this->assertEquals('test', $config['foo']);
    }

    /**
     * @test
     */
    public function append_real_parser()
    {
        $config = $this->make();
        $source = $this->source();
        $parser = new ConfigVariablesParser();
        $source['app']['cache_hint'] = 'cache://{config.cache.driver}';
        $config->appendSource($source);
        $config->appendPostProcessor($parser);
        $this->assertEquals('cache://'.$source['cache']['driver'], $config['app']['cache_hint']);
    }

    /**
     * @test
     */
    public function empty_sources_throw_exception()
    {
        $config = $this->make();
        $this->expectException(UnderflowException::class);
        $config['foo'];
    }

    /**
     * @test
     */
    public function append_unsupported_source_throws_exception()
    {
        $config = $this->make();
        $this->expectException(UnexpectedValueException::class);
        $config->appendSource(new stdClass());
    }

    /**
     * @test
     */
    public function gives_up_after_too_many_sources_were_appended()
    {
        $config = $this->make();
        $this->expectException(OverflowException::class);
        for($i=0; $i<102; $i++) {
            $config->appendSource([]);
        }
    }

    protected function make()
    {
        return new Config();
    }

    protected function source()
    {
        return [
            'foo' => 'bar',
            'app' => [
                'key'       => 'abcdefg',
                'timezone'  =>  'Europe/Berlin'
            ],
            'cache' => [
                'driver' => 'redis'
            ]
        ];
    }
}