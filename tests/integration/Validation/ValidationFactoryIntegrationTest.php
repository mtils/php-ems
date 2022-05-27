<?php
/**
 *  * Created by mtils on 27.05.2022 at 06:48.
 **/

namespace Ems\Validation;

use Ems\AppTrait;
use Ems\Contracts\Core\Subscribable;
use Ems\Contracts\Validation\Validator as ValidatorContract;
use Ems\Contracts\Validation\ValidatorFactory as ValidatorFactoryContract;
use Ems\IntegrationTest;
use Ems\TestData;
use Ems\Testing\LoggingCallable;
use Ems\Validation\Skeleton\ValidationBootstrapper;

use function get_class;

class ValidationFactoryIntegrationTest extends IntegrationTest
{
    use AppTrait;
    use TestData;

    protected $extraBootstrappers = [ValidationBootstrapper::class];

    /**
     * @test
     */
    public function it_creates_validator_by_rules()
    {
        $validator = $this->create(['login' => 'required|min:3|max:128'], self::class);

        $parsed = [
            'login' => [
                'required'  => [],
                'min'       => [3],
                'max'       => [128]
            ]
        ];

        $this->assertInstanceOf(ValidatorContract::class, $validator);
        $this->assertEquals($parsed, $validator->rules());
        $this->assertEquals(self::class, $validator->ormClass());
    }

    /**
     * @test
     */
    public function create_validator_calls_listener()
    {

        $rules = ['login' => 'required|min:3|max:128'];
        $ormClass = self::class;

        $factory = $this->factory();
        if (!$factory instanceof Subscribable) {
            $this->markTestSkipped("The tested class " . get_class($factory) . ' does implement ' . Subscribable::class);
        }

        $validatorListener = new LoggingCallable();
        $factory->on($ormClass, $validatorListener);

        $appListener = new LoggingCallable();
        $this->app()->on(ValidatorContract::class, $appListener);

        $validator = $factory->create($rules, $ormClass);
        $this->assertInstanceOf(ValidatorContract::class, $validator);

        $this->assertCount(1, $validatorListener);
        $this->assertSame($validator, $validatorListener->arg(0));

        $this->assertCount(1, $appListener);
        $this->assertSame($validator, $appListener->arg(0));

    }

    /**
     * @test
     */
    public function get_validator_calls_listener()
    {

        $ormClass = self::class;

        $factory = $this->factory();
        if (!$factory instanceof ValidatorFactory) {
            $this->markTestSkipped("The tested class " . get_class($factory) . ' does implement ' . Validator::class);
        }

        $factory->register($ormClass, ValidationFactoryIntegrationTest_Validator::class);

        $validatorListener = new LoggingCallable();
        $factory->on($ormClass, $validatorListener);

        $appListener = new LoggingCallable();
        $this->app()->on(ValidationFactoryIntegrationTest_Validator::class, $appListener);

        $validator = $factory->get($ormClass);
        $this->assertInstanceOf(ValidationFactoryIntegrationTest_Validator::class, $validator);

        $this->assertCount(1, $validatorListener);
        $this->assertSame($validator, $validatorListener->arg(0));

        $this->assertCount(1, $appListener);
        $this->assertSame($validator, $appListener->arg(0));

    }

    /**
     * @test
     */
    public function even_create_validator_by_container_calls_listener()
    {

        $rules = ['login' => 'required|min:3|max:128'];
        $ormClass = self::class;

        $parsed = [
            'login' => [
                'required'  => [],
                'min'       => [3],
                'max'       => [128]
            ]
        ];

        $factory = $this->factory();
        if (!$factory instanceof Subscribable) {
            $this->markTestSkipped("The tested class " . get_class($factory) . ' does implement ' . Subscribable::class);
        }

        $validatorListener = new LoggingCallable();
        $factory->on($ormClass, $validatorListener);

        $appListener = new LoggingCallable();
        $this->app()->on(ValidatorContract::class, $appListener);

        $validator = $this->app()->create(ValidatorContract::class, [
            'rules'     => $rules,
            'ormClass'  => $ormClass
        ]);
        $this->assertInstanceOf(ValidatorContract::class, $validator);

        $this->assertCount(1, $validatorListener);
        $this->assertSame($validator, $validatorListener->arg(0));

        $this->assertCount(1, $appListener);
        $this->assertSame($validator, $appListener->arg(0));

        $this->assertEquals($parsed, $validator->rules());

    }

    /**
     * @test
     */
    public function even_get_validator_by_container_calls_listener()
    {

        $ormClass = self::class;

        $factory = $this->factory();
        if (!$factory instanceof Subscribable) {
            $this->markTestSkipped("The tested class " . get_class($factory) . ' does implement ' . Subscribable::class);
        }

        $validatorListener = new LoggingCallable();
        $factory->on($ormClass, $validatorListener);

        $appListener = new LoggingCallable();
        $this->app()->on(ValidationFactoryIntegrationTest_Validator::class, $appListener);

        $validator = $this->app()->get(ValidationFactoryIntegrationTest_Validator::class);
        $this->assertInstanceOf(ValidationFactoryIntegrationTest_Validator::class, $validator);

        $this->assertCount(1, $validatorListener);
        $this->assertSame($validator, $validatorListener->arg(0));

        $this->assertCount(1, $appListener);
        $this->assertSame($validator, $appListener->arg(0));

    }

    protected function create(array $rules, string $ormClass='') : ValidatorContract
    {
        return $this->factory()->create($rules, $ormClass);
    }

    protected function factory() : ValidatorFactoryContract
    {
        return $this->app(ValidatorFactoryContract::class);
    }
}

class ValidationFactoryIntegrationTest_Validator extends Validator
{
    public function ormClass() : string
    {
        return ValidationFactoryIntegrationTest::class;
    }
}