<?php

namespace Ems\Core;

use stdClass;
use Ems\Contracts\Core\IOCContainer as ContainerContract;
use Ems\Contracts\Core\ContainerCallable;
use Ems\Testing\LoggingCallable;
use function func_get_args;

class IOCContainerTest extends \Ems\TestCase
{
    public function test_implements_container_interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Core\IOCContainer',
            $this->newContainer()
        );
    }

    public function test_bind_binds_callables_and_returns_container()
    {
        $container = $this->newContainer();
        $this->assertSame($container, $container->bind('foo', function ($app) {}));
        $this->assertTrue($container->bound('foo'));
    }

    /**
     * @expectedException \InvalidArgumentException
     **/
    public function test_binding_of_uncallable_and_nonstring_arg_throws_exception()
    {
        $container = $this->newContainer();
        $container->bind('foo', 43);
    }

    public function test_bound_returns_false_if_binding_doesnt_exist()
    {
        $container = $this->newContainer();
        $this->assertFalse($container->bound('foo'));
    }

    public function test_has_returns_false_if_binding_doesnt_exist()
    {
        $container = $this->newContainer();
        $this->assertFalse($container->has('foo'));
    }

    public function test_invoke_calls_binding()
    {
        $container = $this->newContainer();
        $container->bind('foo', function (ContainerContract $container) {
            return $container;
        });

        $this->assertSame($container, $container('foo'));
    }

    public function test_aliased_invoke_calls_binding()
    {
        $container = $this->newContainer();
        $container->alias('foo', 'bar');
        $container->alias('foo', 'baz');
        $container->bind('foo', function (ContainerContract $container) {
            return $container;
        });

        $this->assertSame($container, $container('bar'));
        $this->assertSame($container, $container('baz'));
    }

    public function test_make_calls_binding()
    {
        $container = $this->newContainer();
        $container->bind('foo', function (ContainerContract $container) {
            return $container;
        });

        $this->assertSame($container, $container->make('foo'));
    }

    public function test_provide_returns_callable_which_throws_parameters_away()
    {
        $container = $this->newContainer();
        $container->bind('foo', function (ContainerContract $container) {
            return $container;
        });

        $provider = $container->provide('foo');
        $this->assertFalse($provider->shouldUseParametersInResolve());

        $this->assertInstanceof(ContainerCallable::class, $provider);

        $this->assertSame($container, $provider());
    }

    public function test_provide_returns_callable_which_passes_parameters()
    {
        $container = $this->newContainer();

        $provider = $container->provide(ContainerTest_ClassParameter::class)->useParametersInResolve();

        $this->assertInstanceof(ContainerCallable::class, $provider);
        $this->assertTrue($provider->shouldUseParametersInResolve());

        $result = $provider('a', 'b', 'c');

        $this->assertInstanceof(ContainerTest_ClassParameter::class, $result);

        $this->assertEquals(['a', 'b', 'c'], $result->args);
    }

    public function test_provide_returns_callable_for_method_call()
    {
        $container = $this->newContainer();
        $custom = $this->mock(ContainerContract::class);

        $container->bind('foo', function () use ($custom) {
            return $custom;
        });

        $provider = $container->provide('foo')->alias();

        $custom->shouldReceive('alias')
               ->with(1,2)
               ->once()
               ->andReturn('tralala');

        $this->assertEquals('alias', $provider->method());
        $this->assertFalse($provider->shouldUseAppCall());
        $this->assertInstanceof(ContainerCallable::class, $provider);

        $this->assertEquals('tralala', $provider(1, 2));
    }

    public function test_provide_returns_callable_for_app_method_call()
    {
        $container = $this->newContainer();
        $custom = $this->mock(ContainerContract::class);

        $container->bind('foo', function () use ($custom) {
            return $custom;
        });

        $provider = $container->provide('foo')->alias();
        $provider->useAppCall(true);
        $custom->shouldReceive('alias')
            ->with(1,2)
            ->once()
            ->andReturn('tralala');

        $this->assertEquals('alias', $provider->method());
        $this->assertTrue($provider->shouldUseAppCall());
        $this->assertInstanceof(ContainerCallable::class, $provider);

        $this->assertEquals('tralala', $provider(1, 2));
    }

    public function test_provide_returns_callable_for_inline_determinism_of_app_call()
    {
        $container = $this->newContainer();
        $custom = $this->mock(ContainerContract::class);

        $container->bind('foo', function () use ($custom) {
            return $custom;
        });

        $provider = $container->provide('foo')->call('alias');

        $custom->shouldReceive('alias')
            ->with(1,2)
            ->once()
            ->andReturn('tralala');

        $this->assertEquals('alias', $provider->method());
        $this->assertTrue($provider->shouldUseAppCall());
        $this->assertInstanceof(ContainerCallable::class, $provider);

        $this->assertEquals('tralala', $provider(1, 2));
    }

    public function test_invoke_of_shared_binding_returns_same_object()
    {
        $container = $this->newContainer();

        $shareReturn = $container->bind('foo', function (ContainerContract $container) {
            return new stdClass();
        }, true);

        // Check if container is returned
        $this->assertSame($container, $shareReturn);

        $result = $container('foo');

        $this->assertInstanceOf('stdClass', $result);
        $this->assertSame($result, $container('foo'));
        $this->assertSame($result, $container('foo'));

        $this->assertTrue($container->bound('foo'));
    }

    public function test_share_creates_singleton()
    {
        $container = $this->newContainer();

        $concreteClass = ContainerTest_Class::class;
        $concrete = new $concreteClass();
        $factory = new LoggingCallable(function () use ($concrete) {
            return $concrete;
        });

        $container->share(ContainerTest_Interface::class, $factory);

        $this->assertSame($concrete, $container->make(ContainerTest_Interface::class));
        $this->assertCount(1, $factory);
        $this->assertSame($concrete, $container->make(ContainerTest_Interface::class));
        $this->assertCount(1, $factory);
    }

    public function test_share_does_not_end_in_endless_recursion()
    {
        $container = $this->newContainer();

        $concreteClass = ContainerTest_Class::class;

        $factory = new LoggingCallable(function () use ($concreteClass, $container) {
            return $container->make($concreteClass);
        });

        $container->share(ContainerTest_Interface::class, $factory);

        $concrete = $container->make(ContainerTest_Interface::class);
        $this->assertInstanceOf($concreteClass, $concrete);
        $this->assertCount(1, $factory);
        $this->assertSame($concrete, $container->make(ContainerTest_Interface::class));
        $this->assertCount(1, $factory);
    }

    public function test_share_does_not_end_in_endless_recursion_when_abstract_and_concrete_is_same()
    {
        $container = $this->newContainer();

        $concreteClass = ContainerTest_Class::class;

        $factory = new LoggingCallable(function () use ($concreteClass, $container) {
            return $container->create($concreteClass);
        });
        $container->share(ContainerTest_Class::class, $factory);

        $this->assertInstanceOf($concreteClass, $container->make(ContainerTest_Class::class));
        $this->assertCount(1, $factory);
        $this->assertInstanceOf($concreteClass, $container->make(ContainerTest_Class::class));
        $this->assertCount(1, $factory);
    }

    public function test_share_with_interface_and_class()
    {
        $container = $this->newContainer();

        $concreteClass = ContainerTest_Class::class;
        $abstract = ContainerTest_Interface::class;

        $container->share($abstract, $concreteClass);

        $object = $container->make(ContainerTest_Interface::class);

        $this->assertInstanceOf($concreteClass, $object);
        $this->assertSame($object, $container->make(ContainerTest_Interface::class));

    }

    public function test_share_with_just_a_class()
    {
        $container = $this->newContainer();

        $concreteClass = ContainerTest_Class::class;
        $abstract = ContainerTest_Interface::class;

        $container->share($concreteClass);

        $object = $container->make(ContainerTest_Class::class);

        $this->assertInstanceOf($concreteClass, $object);
        $this->assertSame($object, $container->make(ContainerTest_Class::class));

    }

    public function test_share_with_just_a_class_and_previously_bound_factory()
    {
        $container = $this->newContainer();

        $concreteClass = ContainerTest_Class::class;
        $abstract = ContainerTest_Interface::class;

        $container->share($concreteClass);

        $object = $container->make(ContainerTest_Class::class);

        $this->assertInstanceOf($concreteClass, $object);
        $this->assertSame($object, $container->make(ContainerTest_Class::class));

    }

    public function test_invoke_of_unshared_binding_returns_different_object()
    {
        $container = $this->newContainer();
        $container->bind('foo', function (ContainerContract $container) {
            return new stdClass();
        });

        $result = $container('foo');

        $this->assertInstanceOf('stdClass', $result);
        $this->assertNotSame($result, $container('foo'));
        $this->assertNotSame($result, $container('foo'));
        $this->assertNotSame($result, $container('foo'));
    }

    public function test_shareInstance_shares_passed_instance()
    {
        $container = $this->newContainer();
        $shared = new stdClass();

        $shareReturn = $container->instance('foo', $shared);

        // Check if container is returned
        $this->assertSame($container, $shareReturn);

        $this->assertSame($shared, $container('foo'));
        $this->assertSame($shared, $container('foo'));

        $this->assertTrue($container->bound('foo'));
    }

    public function test_resolved_and_bound_returns_correct_values_on_bind()
    {
        $container = $this->newContainer();

        $this->assertFalse($container->bound('foo'));
        $this->assertFalse($container->resolved('foo'));

        $container->bind('foo', function (ContainerContract $container) {
            return new stdClass();
        });

        $this->assertTrue($container->bound('foo'));
        $this->assertFalse($container->resolved('foo'));

        $result = $container('foo');

        $this->assertTrue($container->bound('foo'));
        $this->assertTrue($container->resolved('foo'));
    }

    public function test_resolved_and_bound_returns_correct_values_on_shared_binding()
    {
        $container = $this->newContainer();

        $this->assertFalse($container->bound('foo'));
        $this->assertFalse($container->resolved('foo'));

        $container->bind('foo', function (ContainerContract $container) {
            return new stdClass();
        }, true);

        $this->assertTrue($container->bound('foo'));
        $this->assertFalse($container->resolved('foo'));

        $result = $container('foo');

        $this->assertTrue($container->bound('foo'));
        $this->assertTrue($container->resolved('foo'));
    }

    public function test_resolved_and_bound_returns_correct_values_on_shared_instance()
    {
        $container = $this->newContainer();

        $this->assertFalse($container->bound('foo'));
        $this->assertFalse($container->resolved('foo'));

        $instance = new stdClass();

        $container->instance('foo', $instance);

        $this->assertTrue($container->bound('foo'));
        $this->assertTrue($container->resolved('foo'));

        $result = $container('foo');

        $this->assertTrue($container->bound('foo'));
        $this->assertTrue($container->resolved('foo'));
    }

    public function test_resolving_listener_gets_called_on_absolute_equal_abstract_and_returns_itself()
    {
        $callable = new LoggingCallable();
        $container = $this->newContainer();

        $this->assertSame($container, $container->resolving('foo', $callable));

        $container->bind('foo', function (ContainerContract $container) {
            return 'bar';
        });

        $this->assertEquals('bar', $container('foo'));

        $this->assertEquals('bar', $callable->arg(0));

        $this->assertCount(1, $callable);
    }

    public function test_afterResolving_listener_gets_called_on_absolute_equal_abstract_and_returns_itself()
    {
        $callable = new LoggingCallable();
        $container = $this->newContainer();

        $this->assertSame($container, $container->resolving('foo', $callable));
        $this->assertSame($container, $container->afterResolving('foo', $callable));

        $container->bind('foo', function (ContainerContract $container) {
            return 'bar';
        });

        $this->assertEquals('bar', $container('foo'));

        $this->assertEquals('bar', $callable->arg(0));
        $this->assertSame($container, $callable->arg(1));

        $this->assertCount(2, $callable);
    }

    public function test_resolving_listener_gets_called_on_instance_of_abstract()
    {
        $aliasListener = new LoggingCallable();
        $interfaceListener = new LoggingCallable();
        $classListener = new LoggingCallable();
        $class2Listener = new LoggingCallable();
        $otherListener = new LoggingCallable();

        $container = $this->newContainer();

        $container->resolving('foo', $aliasListener);
        $container->resolving('Ems\Core\ContainerTest_Interface', $interfaceListener);
        $container->resolving('Ems\Core\ContainerTest_Class', $classListener);
        $container->resolving('Ems\Core\ContainerTest_Class2', $class2Listener);
        $container->resolving('Ems\Core\ContainerTest', $otherListener);

        $container->bind('foo', function (ContainerContract $container) {
            return new ContainerTest_Class2();
        });

        $result = $container('foo');

        $this->assertInstanceOf('Ems\Core\ContainerTest_Class2', $result);

        $this->assertCount(1, $interfaceListener);
        $this->assertSame($result, $interfaceListener->arg(0));

        $this->assertCount(1, $classListener);
        $this->assertSame($result, $classListener->arg(0));

        $this->assertCount(1, $class2Listener);
        $this->assertSame($result, $class2Listener->arg(0));

        $this->assertCount(0, $otherListener);
    }

    public function test_resolving_listener_gets_called_on_shareInstance()
    {
        $callable = new LoggingCallable();
        $container = $this->newContainer();

        $instance = new stdClass();

        $container->resolving('foo', $callable);
        $container->instance('foo', $instance);

        $this->assertSame($instance, $container('foo'));

        $this->assertSame($instance, $callable->arg(0));
        $this->assertCount(1, $callable);
    }

    public function test_invoke_creates_unbound_classes()
    {
        $class = 'Ems\Core\ContainerTest_Class2';
        $this->assertInstanceOf($class, $this->newContainer()->__invoke($class));
    }

    public function test_invoke_resolves_constructor_parameters()
    {
        $container = $this->newContainer();
        $class = 'Ems\Core\ContainerTest_ClassDependencies';

        $interfaceImplementor = new ContainerTest_Class();

        $container->instance('Ems\Core\ContainerTest_Interface', $interfaceImplementor);

        $result = $container($class);

        $this->assertInstanceOf($class, $result);
        $this->assertSame($interfaceImplementor, $result->interface);
        $this->assertInstanceOf('Ems\Core\ContainerTest_Class', $result->classObject);
        $this->assertInstanceOf('Ems\Core\ContainerTest_Class2', $result->class2Object);
    }

    public function test_invoke_resolves_constructor_parameters_and_overwrites_with_all_positioned_parameters()
    {
        $container = $this->newContainer();
        $class = 'Ems\Core\ContainerTest_ClassDependencies';

        $interfaceImplementor = new ContainerTest_Class();

        $classObject = $container(ContainerTest_Class::class);
        $class2Object = $container(ContainerTest_Class2::class);

        $container->instance('Ems\Core\ContainerTest_Interface', $interfaceImplementor);

        $result = $container($class, [$interfaceImplementor, $classObject, $class2Object]);

        $this->assertInstanceOf($class, $result);
        $this->assertSame($interfaceImplementor, $result->interface);
        $this->assertSame($classObject, $result->classObject);
        $this->assertSame($class2Object, $result->class2Object);
    }

    /**
     * @throws \ReflectionException
     */
    public function test_invoke_resolves_constructor_parameters_and_overwrites_with_all_named_parameters()
    {
        $container = $this->newContainer();
        $class = 'Ems\Core\ContainerTest_ClassDependencies';

        $interfaceImplementor = new ContainerTest_Class();

        $classObject = $container(ContainerTest_Class::class);
        $class2Object = $container(ContainerTest_Class2::class);


        $result = $container($class, [
            'interface' => $interfaceImplementor,
            'classObject' => $classObject,
            'class2Object' => $class2Object
        ]);

        $this->assertInstanceOf($class, $result);
        $this->assertSame($interfaceImplementor, $result->interface);
        $this->assertSame($classObject, $result->classObject);
        $this->assertSame($class2Object, $result->class2Object);
    }

    public function test_create_ignores_shared_binding()
    {
        $container = $this->newContainer();
        $container->share(ContainerTest_Class::class);

        $object = $container->create(ContainerTest_Class::class);
        $this->assertInstanceOf( ContainerTest_Class::class, $object);
        $singleton = $container->make(ContainerTest_Class::class);
        $this->assertNotSame($object, $singleton);
        $this->assertSame($singleton, $container->make(ContainerTest_Class::class));
        $this->assertNotSame($object, $container->create(ContainerTest_Class::class));
    }

    public function test_create_uses_overwritten_class_if_one_was_bound()
    {
        $container = $this->newContainer();
        $container->bind(ContainerTest_Class::class, ContainerTest_Class2::class);

        $object = $container->create(ContainerTest_Class::class);
        $this->assertInstanceOf( ContainerTest_Class::class, $object);

    }

    public function test_create_uses_exact_class_if_forced()
    {
        $container = $this->newContainer();
        $container->bind(ContainerTest_Class::class, ContainerTest_Class2::class);

        $object = $container->create(ContainerTest_Class::class, [], true);
        $this->assertInstanceOf( ContainerTest_Class::class, $object);
        $this->assertFalse($object instanceof ContainerTest_Class2);

    }

    public function test_bind_string_will_bind_bound_class()
    {
        $container = $this->newContainer();
        $container->bind(ContainerTest_Interface::class, ContainerTest_Class::class);

        $result = $container(ContainerTest_Interface::class);

        $this->assertInstanceOf(ContainerTest_Class::class, $result);
    }

    public function test_call_injects_dependencies()
    {
        $container = $this->newContainer();
        $object1 = new ContainerTest_Class();

        $container->instance(ContainerTest_Class::class, $object1);

        $invoke = $container->make(ContainerTest_Class2::class);

        $this->assertSame([$object1], $container->call([$invoke, 'need']));

    }

    public function test_call_injects_double_dependencies()
    {
        $container = $this->newContainer();
        $object1 = new ContainerTest_Class();

        $container->instance(ContainerTest_Class::class, $object1);

        $invoke = $container->make(ContainerTest_Class2::class);

        $this->assertSame([$object1, $object1], $container->call([$invoke, 'needDouble']));

    }

    public function test_call_injects_multiple_dependencies()
    {
        $container = $this->newContainer();
        $object1 = new ContainerTest_Class();
        $object2 = new ContainerTest_Class2();

        $container->instance(ContainerTest_Class::class, $object1);
        $container->instance(ContainerTest_Interface::class, $object2);

        $invoke = $container->make(ContainerTest_Class2::class);

        $result = $container->call([$invoke, 'needMany']);
        $this->assertCount(2, $result);
        $this->assertSame($object1, $result[0]);
        $this->assertSame($object2, $result[1]);

    }

    public function test_call_injects_dependencies_and_parameters()
    {
        $container = $this->newContainer();
        $object1 = new ContainerTest_Class();
        $parameter = 12;

        $container->instance(ContainerTest_Class::class, $object1);

        $invoke = $container->make(ContainerTest_Class2::class);

        $this->assertSame([$object1, $parameter], $container->call([$invoke, 'needParameter'], [$parameter]));

    }

    public function test_call_injects_multiple_dependencies_and_parameters()
    {
        $container = $this->newContainer();
        $object1 = new ContainerTest_Class();
        $object2 = new ContainerTest_Class2();
        $parameter1 = 12;
        $parameter2 = 88;

        $container->instance(ContainerTest_Class::class, $object1);
        $container->instance(ContainerTest_Interface::class, $object2);

        $invoke = $container->make(ContainerTest_Class2::class);

        $awaitedArgs = [$object1, $parameter1, $object2, $parameter2];
        $this->assertSame($awaitedArgs, $container->call([$invoke, 'needManyParameters'], [$parameter1, $parameter2]));

    }

    public function test_call_injects_multiple_dependencies_and_parameters_in_different_order()
    {
        $container = $this->newContainer();
        $object1 = new ContainerTest_Class();
        $object2 = new ContainerTest_Class2();
        $parameter1 = 12;
        $parameter2 = 88;

        $container->instance(ContainerTest_Class::class, $object1);
        $container->instance(ContainerTest_Interface::class, $object2);

        $invoke = $container->make(ContainerTest_Class2::class);

        $awaitedArgs = [$parameter1, $object1, $parameter2, $object2];
        $this->assertSame($awaitedArgs, $container->call([$invoke, 'needManyParametersReversed'], [$parameter1, $parameter2]));

    }

    public function test_call_takes_passed_parameters_and_not_injected()
    {
        $container = $this->newContainer();
        $object1 = new ContainerTest_Class();
        $object2 = new ContainerTest_Class2();
        $object3 = new ContainerTest_Class();

        $container->instance(ContainerTest_Class::class, $object1);
        $container->instance(ContainerTest_Interface::class, $object2);

        $invoke = $container->make(ContainerTest_Class2::class);

        $result = $container->call([$invoke, 'needMany'], ['object2' => $object3]);
        $this->assertCount(2, $result);
        $this->assertSame($object1, $result[0]);
        $this->assertSame($object3, $result[1]);

    }

    protected function newContainer()
    {
        return new IOCContainer();
    }
}

interface ContainerTest_Interface
{
}

class ContainerTest_Class implements ContainerTest_Interface
{
}

class ContainerTest_Class2 extends ContainerTest_Class
{
    public function need(ContainerTest_Class $object)
    {
        return func_get_args();
    }

    public function needDouble(ContainerTest_Class $object, ContainerTest_Class $object2)
    {
        return func_get_args();
    }

    public function needMany(ContainerTest_Class $object, ContainerTest_Interface $object2)
    {
        return func_get_args();
    }

    public function needParameter(ContainerTest_Class $object, $objectId)
    {
        return func_get_args();
    }

    public function needManyParameters(ContainerTest_Class $object, $objectId, ContainerTest_Interface $object2, $object2Id)
    {
        return func_get_args();
    }

    public function needManyParametersReversed($objectId, ContainerTest_Class $object, $object2Id, ContainerTest_Interface $object2)
    {
        return func_get_args();
    }
}

class ContainerTest_ClassDependencies
{
    public function __construct(ContainerTest_Interface $interface,
                                ContainerTest_Class $classObject,
                                ContainerTest_Class2 $class2Object,
                                $param=null)
    {
        $this->interface = $interface;
        $this->classObject = $classObject;
        $this->class2Object = $class2Object;
        $this->param = $param;
    }
}

class ContainerTest_ClassParameter
{
    public $args;

    public function __construct($a, $b, $c)
    {
        $this->args = func_get_args();
    }
}

