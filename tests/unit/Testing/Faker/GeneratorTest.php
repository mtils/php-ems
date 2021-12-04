<?php
/**
 *  * Created by mtils on 04.12.2021 at 15:00.
 **/

namespace unit\Testing\Faker {

    use Ems\TestCase;
    use Ems\Testing\Faker\Factory;
    use GeneratorTestFactoryNS\Factories\GeneratorTest_EntityFactory;
    use GeneratorTestNS\App\Orm\GeneratorTest_Entity;

    class GeneratorTest extends TestCase
    {
        /**
         * @test
         */
        public function add_a_factory()
        {
            $generator = $this->generator();
            $factory = $generator->getInstanceFactory(
                GeneratorTest_Entity::class
            );
            $this->assertInstanceOf(GeneratorTest_EntityFactory::class, $factory);
        }

        /**
         * @test
         */
        public function use_a_factory_for_attributes()
        {
            $generator = $this->generator();

            /** @var FactoryTest_Entity $instance */
            $data = $generator->attributes(GeneratorTest_Entity::class);
            $this->assertGreaterThan(49, $data['number']);
            $this->assertLessThan(2501, $data['number']);
            $this->assertNotEmpty($data['name']);
        }

        /**
         * @test
         */
        public function use_a_factory_with_one_instance()
        {
            $generator = $this->generator();

            /** @var FactoryTest_Entity $instance */
            $instance = $generator->instance(GeneratorTest_Entity::class);
            $this->assertInstanceOf(GeneratorTest_Entity::class, $instance);
            $this->assertGreaterThan(49, $instance->number);
            $this->assertLessThan(2501, $instance->number);
            $this->assertNotEmpty($instance->name);
        }

        /**
         * @test
         */
        public function use_a_factory_with_multiple_instance()
        {

            $generator = $this->generator();

            /** @var FactoryTest_Entity[] $instances */
            $instances = $generator->instances(GeneratorTest_Entity::class, 10);

            foreach ($instances as $instance) {
                $this->assertInstanceOf(GeneratorTest_Entity::class, $instance);
                $this->assertGreaterThan(49, $instance->number);
                $this->assertLessThan(2501, $instance->number);
                $this->assertNotEmpty($instance->name);
            }

        }

        protected function generator()
        {
            $generator = Factory::create();
            $generator->mapInstanceFactoryNamespace('GeneratorTestNS\App\Orm', 'GeneratorTestFactoryNS\Factories');
            return $generator;
        }
    }
}
namespace GeneratorTestNS\App\Orm {
    class GeneratorTest_Entity
    {
        public $number = 0;
        public $name = '';
    }
}

namespace GeneratorTestFactoryNS\Factories {

    use Ems\Testing\Faker\Generator;
    use Ems\Testing\Faker\InstanceFactory;

    class GeneratorTest_EntityFactory extends InstanceFactory
    {
        public function data(string $class, Generator $faker): array
        {
            return [
                'number'    => $faker->numberBetween(50, 2500),
                'name'      => $faker->name
            ];
        }

    }
}

