<?php


namespace Ems\Foundation;

use Ems\Contracts\Foundation\InputProcessor as InputProcessorContract;
use Ems\Contracts\Foundation\InputNormalizer as InputNormalizerContract;
use Ems\Contracts\Foundation\InputNormalizerFactory as InputNormalizerFactoryContract;
use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\NamedCallableChain as ChainContract;
use Ems\Validation\GenericValidator;
use Ems\Contracts\Validation\ValidatorFactory;
use Ems\Core\NamedObject;
use Ems\Testing\LoggingCallable;
use Ems\Contracts\Core\Errors\NotFound;
use Ems\Testing\Cheat;
use Ems\Validation\GenericValidatorFactory;

require_once __DIR__ . '/InputNormalizerTest.php';


class InputNormalizerFactoryTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(InputNormalizerFactoryContract::class, $this->newFactory());
    }

    public function test_adjuster()
    {
        $adjuster = $this->mock(InputProcessorContract::class);
        $normalizer = $this->newFactory(null, $adjuster);

        $this->assertSame($adjuster, $normalizer->adjuster());
    }

    public function test_caster()
    {
        $caster = $this->mock(InputProcessorContract::class);
        $normalizer = $this->newFactory(null, null, $caster);

        $this->assertSame($caster, $normalizer->caster());
    }

    public function test_validatorFactory()
    {
        $factory = $this->mock(ValidatorFactory::class);
        $normalizer = $this->newFactory($factory);

        $this->assertSame($factory, $normalizer->validatorFactory());
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_normalizer_with_wrong_segment_count_throws_exception()
    {
        $normalizer = $this->newFactory()->normalizer('http.get');
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_normalizer_with_wildcards_throws_exception()
    {
        $normalizer = $this->newFactory()->normalizer('http.*.get');
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_extend_with_wrong_segment_count_throws_exception()
    {
        $normalizer = $this->newFactory()->extend('http.get', function () {});
    }

    public function test_extend_with_wrong_segment_count_throws_no_exception_if_star_shortcut_passed()
    {
        $normalizer = $this->newFactory()->extend('*', function () {});
    }

    public function test_createPriorityList_creates_right_lists()
    {
        $normalizer = Cheat::a($this->newFactory());

        $tests = [
            'http.browser.get'      => [
                '*.*.*',
                'http.*.*',
                '*.browser.*',
                '*.*.get',
                'http.browser.*',
                'http.*.get',
                '*.browser.get',
                'http.browser.get'
            ],
        ];

        foreach ($tests as $inputType=>$awaited) {
            $this->assertEquals($awaited, $normalizer->createPriorityList($inputType));
        }

        // Check cache usage in coverage analysis
        foreach ($tests as $inputType=>$awaited) {
            $this->assertEquals($awaited, $normalizer->createPriorityList($inputType));
        }

    }

    public function test_normalize_with_unknown_inputType_does_not_throw_exception()
    {

        $factory = $this->newFactory();
        $input = ['a' => 'b'];
        $this->assertEquals($input, $factory->normalizer('http.browser.get')->normalize($input));
    }

    public function test_normalize_creates_normalizer()
    {
        $factory = $this->newFactory();
        $resource = new NamedObject;
        $locale='fr';

        $factory->extend('http.browser.get', function ($normalizer, $inputType, $passedResource, $passedLocale) use ($resource, $locale) {
            $this->assertInstanceOf(InputNormalizer::class, $normalizer);
            $this->assertEquals('http.browser.get', $inputType);
            $this->assertSame($resource, $passedResource);
            $this->assertSame($locale, $passedLocale);
        });


        $normalizer = $factory->normalizer('http.browser.get', $resource, $locale);
        $this->assertInstanceOf(InputNormalizerContract::class, $normalizer);
    }

    public function test_normalize_creates_normalizer_with_fuzzy_pattern()
    {
        $factory = $this->newFactory();
        $resource = new NamedObject;
        $locale='fr';

        $factory->extend('http.*.get', function ($normalizer, $inputType, $passedResource, $passedLocale) use ($resource, $locale) {
            $this->assertInstanceOf(InputNormalizer::class, $normalizer);
            $this->assertEquals('http.browser.get', $inputType);
            $this->assertSame($resource, $passedResource);
            $this->assertSame($locale, $passedLocale);
        });


        $normalizer = $factory->normalizer('http.browser.get', $resource, $locale);
        $this->assertInstanceOf(InputNormalizerContract::class, $normalizer);
    }

    public function test_normalize_matches_multiple_extensions_with_fuzzy_patterns()
    {
        $factory = $this->newFactory();
        $resource = new NamedObject;

        $e = [
            'http.*.get'        => new LoggingCallable,
            'http.*.*'          => new LoggingCallable,
            'http.browser.*'    => new LoggingCallable,
            'http.browser.post' => new LoggingCallable,
            'http.browser.get'  => new LoggingCallable
        ];

        foreach ($e as $pattern=>$extension) {
            $factory->extend($pattern, $extension);
        }



        $normalizer = $factory->normalizer('http.browser.get', $resource);
        $this->assertInstanceOf(InputNormalizerContract::class, $normalizer);

        $this->assertCount(1, $e['http.*.get']);
        $this->assertCount(1, $e['http.*.*']);
        $this->assertCount(1, $e['http.browser.*']);
        $this->assertCount(0, $e['http.browser.post']);
        $this->assertCount(1, $e['http.browser.get']);

        $normalizer2 = $factory->normalizer('http.browser.post', $resource);
        $this->assertInstanceOf(InputNormalizerContract::class, $normalizer);
        $this->assertNotSame($normalizer, $normalizer2);

        $this->assertCount(1, $e['http.*.get']);
        $this->assertCount(2, $e['http.*.*']);
        $this->assertCount(2, $e['http.browser.*']);
        $this->assertCount(1, $e['http.browser.post']);
        $this->assertCount(1, $e['http.browser.get']);

        $factory->normalizer('http.api.get', $resource);

        $this->assertCount(2, $e['http.*.get']);
        $this->assertCount(3, $e['http.*.*']);
        $this->assertCount(2, $e['http.browser.*']);
        $this->assertCount(1, $e['http.browser.post']);
        $this->assertCount(1, $e['http.browser.get']);

        $factory->normalizer('http.api.post', $resource);

        $this->assertCount(2, $e['http.*.get']);
        $this->assertCount(4, $e['http.*.*']);
        $this->assertCount(2, $e['http.browser.*']);
        $this->assertCount(1, $e['http.browser.post']);
        $this->assertCount(1, $e['http.browser.get']);

        $factory->normalizer('console.bash.argv', $resource);

        $this->assertCount(2, $e['http.*.get']);
        $this->assertCount(4, $e['http.*.*']);
        $this->assertCount(2, $e['http.browser.*']);
        $this->assertCount(1, $e['http.browser.post']);
        $this->assertCount(1, $e['http.browser.get']);

    }

    public function test_onBeforeAndAfter_hooks()
    {

        $factory = $this->newFactory();

        $input = ['a' => 1, 'b' => 2];
        $adjusted = ['a' => 1, 'b' => 2, 'c' => 3];
        $casted = ['a' => 1];

        $resource = new NamedObject;

        $handler = function ($input) {
            return $input;
        };

        $listeners = [
            'adjust.before'   => new LoggingCallable($handler),
            'adjust.after'    => new LoggingCallable($handler),
            'validate.before' => new LoggingCallable($handler),
            'validate.after'  => new LoggingCallable($handler),
            'cast.before'     => new LoggingCallable($handler),
            'cast.after'      => new LoggingCallable($handler),
        ];

        foreach ($listeners as $event=>$listener) {
            list($hook, $position) = explode('.', $event);
            $method = "on" . ucfirst($position);
            $factory->$method($hook, $listener);
        }

        $normalizer = $factory->normalizer('http.browser.post');

        $this->assertEquals($input, $normalizer->normalize($input, $resource));

        foreach ($listeners as $event=>$listener) {
            $this->assertCount(1, $listener);
        }

        $this->assertEquals($input, $listeners['adjust.before']->arg(0));
        $this->assertEquals($input, $listeners['adjust.after']->arg(0));
        $this->assertEquals($input, $listeners['validate.before']->arg(0));
        $this->assertEquals($input, $listeners['validate.after']->arg(0));
        $this->assertEquals($input, $listeners['cast.before']->arg(0));
        $this->assertEquals($input, $listeners['cast.after']->arg(0));
    }

    protected function newFactory(ValidatorFactory $validator=null, InputProcessorContract $adjuster=null, InputProcessorContract $caster=null)
    {
        $validator = $validator ?: $this->newValidatorFactory();
        $adjuster = $adjuster ?: new InputProcessor;
        $caster = $caster ?: new InputProcessor;
        return new InputNormalizerFactory($validator, $adjuster, $caster);
    }

    protected function newValidatorFactory()
    {
        $createValidator = function () {
            return new GenericValidator(['a' => 'b'], function ($baseValidator, $input) {
                return $input;
            });
        };
        return new GenericValidatorFactory($createValidator);
    }
}
