<?php

namespace Ems\Model;

use Ems\Contracts\Model\OrmCollection;
use Ems\Contracts\Model\OrmObject as OrmObjectContract;
use Ems\Testing\LoggingCallable;
use function iterator_to_array;

class OrmObjectTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            OrmObjectContract::class,
            $this->newObject()
        );
    }

    public function test_isset_returns_false_if_missing()
    {
        $object = $this->newObject();
        $this->assertFalse(isset($object->_foo));
    }

    public function test_isset_returns_true_if_setted()
    {
        $object = $this->newObject();
        $object->foo = true;
        $this->assertTrue(isset($object->foo));
    }

    public function test___get_returns_value_if_setted()
    {
        $object = $this->newObject();
        $object->foo = 'bar';
        $this->assertEquals('bar', $object->foo);
    }

    public function test___unset_removes_value()
    {
        $object = $this->newObject();
        $object->foo = 'bar';
        $this->assertTrue(isset($object->foo));
        unset($object->foo);
        $this->assertFalse(isset($object->foo));
    }

    public function test_keys_returns_assigned_keys()
    {

        $object = $this->newObject();
        $object->foo = 'bar';
        $object->bar = 'baz';

        $this->assertEquals(['foo', 'bar'], $object->keys()->getSource());
    }

    public function test_toArray_returns_setted_data()
    {

        $awaited = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];

        $object = $this->newObject();

        foreach ($awaited as $key=>$value) {
            $object->$key = $value;
        }

        $this->assertEquals($awaited, $object->toArray());
    }

    public function test_fill_by___construct()
    {

        $awaited = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];

        $object = $this->newObject($awaited, true);

        $this->assertEquals($awaited, $object->toArray());
        $this->assertFalse($object->isNew());


    }

    public function test_isNew_returns_true_if_new()
    {
        $this->assertTrue($this->newObject()->isNew());
    }


    public function test_wasModified_returns_false_without_parameters()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data);

        $this->assertFalse($object->wasModified());
        $this->assertFalse($object->isLazyLoadKey('foo'));
    }

    public function test_wasModified_returns_true_without_parameters()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];

        $object = $this->newObject($data);

        $object->foo = 'blink';

        $this->assertTrue($object->wasModified());
    }

    public function test_wasModified_returns_false_with_parameters()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data);

        $object->foo = 'blink';

        $this->assertFalse($object->wasModified('bar'));
    }

    public function test_wasModified_returns_true_with_parameters()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data);

        $object->foo = 'blink';

        $this->assertTrue($object->wasModified('foo'));
    }

    public function test_wasModified_returns_true_with_parameters_if_original_key_didnt_exist()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data);

        $object->new = 'bub';

        $this->assertTrue($object->wasModified('new'));
    }

    public function _test_wasModified_without_parameters_returns_true_if_original_key_was_deleted_in_attributes()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data);

        unset($object->bar);

        $this->assertTrue($object->wasModified());
    }

    public function test_wasModified_returns_true_if_original_key_was_deleted_in_attributes()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data);

        unset($object->bar);

        $this->assertTrue($object->wasModified('bar'));
    }

    public function test_wasLoaded_returns_true_without_parameters()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data, true);

        $this->assertTrue($object->wasLoaded());
    }

    public function test_wasLoaded_returns_false_with_parameters()
    {
        $object = $this->newObject(['hihi' => 'hahaha']);
        $this->assertFalse($object->wasLoaded('hoho'));
    }

    public function test_wasLoaded_returns_true_with_parameters()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data, true);

        foreach (['foo', 'bar'] as $key) {
            $this->assertTrue($object->wasLoaded($key));
        }

        $this->assertTrue($object->wasLoaded('foo', 'bar'));
        $this->assertTrue($object->wasLoaded(['foo', 'bar']));
    }

    public function test_wasLoaded_returns_false_with_unknown_parameters()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data, true);

        foreach (['acme', 'baz'] as $key) {
            $this->assertFalse($object->wasLoaded($key));
        }
    }

    public function test_wasLoaded_returns_false_with_setted_parameters()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data, true);

        $object->acme = 'hihi';

        foreach (['acme', 'baz'] as $key) {
            $this->assertFalse($object->wasLoaded($key));
        }
    }

    public function test_getOriginal_without_parameters_returns_original_values()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data);

        $defaults = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];

        $this->assertEquals($defaults, $object->getOriginal());
    }

    public function test_getOriginal_with_parameters_returns_original_values()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data);

        $this->assertEquals('bar', $object->getOriginal('foo'));
        $this->assertEquals('baz', $object->getOriginal('bar'));

        $object->foo = 'fii';
        $object->bar = 'boo';

        $this->assertEquals('bar', $object->getOriginal('foo'));
        $this->assertEquals('baz', $object->getOriginal('bar'));

        $this->assertEquals([
            'foo' => 'fii',
            'bar' => 'boo'
        ], $object->toArray());

    }

    public function test_getOriginal_with_parameters_returns_default_if_original_not_setted()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data);

        $this->assertEquals('blong', $object->getOriginal('bling', 'blong'));
    }

    public function test_reset_resets_to_original_attributes()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data);

        $this->assertEquals($data, $object->toArray());

        $object->foo = 'uncle';

        $data2 = [
            'foo' => 'uncle',
            'bar' => 'baz'
        ];

        $this->assertEquals($data2, $object->toArray());

        $object->reset();

        $this->assertEquals($data, $object->toArray());
    }

    public function test_getId_returns_id_from_array()
    {
        $data = [
            'id' => 42,
            'foo' => 'bar',
            'bar' => 'baz'
        ];
        $object = $this->newObject($data);

        $this->assertEquals(42, $object->getId());
    }

    /**
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     */
    public function test_resourceName_throws_exception_if_not_implemented()
    {
        $this->newObject()->resourceName();
    }

    /**
     * @expectedException \Ems\Contracts\Model\Exceptions\RelationNotFoundException
     */
    public function test_getRelation_throws_exception_if_key_is_no_relation()
    {
        $object = $this->newObject();
        $object->getRelation('users');
    }

    public function test_getRelation_returns_assigned_relation()
    {
        $object = $this->newObjectWithRelations();

        $relation = $object->getRelation('users');
        $this->assertInstanceOf(Relation::class, $relation);
        $this->assertTrue($relation->isParentRequired());
        $this->assertFalse($relation->belongsToMany());
        $this->assertFalse($relation->hasMany());
        $this->assertFalse($relation->isRequired());
        $this->assertInstanceOf(Address::class, $relation->getParent());
        $this->assertEquals('users', $relation->getParentKey());
        $this->assertInstanceOf(OrmObjectTestUser::class, $relation->getRelatedObject());
    }

    public function test_isRelation_on_not_existing_relation()
    {
        $this->assertFalse($this->newObject()->isRelation('foo'));
    }

    /**
     * @expectedException \Ems\Contracts\Model\Exceptions\RelationNotFoundException
     */
    public function test_getRelated_throws_exception_if_key_is_no_relation()
    {
        $object = $this->newObject();
        $object->getRelated('users');
    }

    public function test_getRelated_returns_related_if_already_assigned()
    {
        $object = $this->newObject();
        $user = new OrmObjectTestUser();
        $object->users = $user;

        $this->assertSame($user, $object->getRelated('users'));
    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\ConfigurationError
     */
    public function test_getRelated_throws_exception_if_no_relation_loader_assigned()
    {
        $object = $this->newObjectWithRelations();
        $object->getRelated('users');
    }

    public function test_getRelated_fetched_related_from_relation_loader()
    {

        $user = new OrmObjectTestUser();


        $relationLoader = new LoggingCallable(function () use ($user) {
            return $user;
        });

        $object = $this->newObjectWithRelations([], false, $relationLoader);

        $this->assertFalse($object->relatedLoaded('users'));

        $this->assertSame($user, $object->getRelated('users'));

        $this->assertTrue($object->relatedLoaded('users'));

        $this->assertSame($object, $relationLoader->arg(0));
        $this->assertEquals('users', $relationLoader->arg(1));
        $this->assertCount(1, $relationLoader);

        // Fail if it is triggered twice
        $this->assertSame($user, $object->getRelated('users'));
        $this->assertCount(1, $relationLoader);
    }

    public function test_getRelated_fetches_many_related_from_relation_loader()
    {

        $invoices = [
            new Invoice(), new Invoice(), new Invoice()
        ];

        $getter = function () use ($invoices) {
            return $invoices;
        };

        $relationLoader = function ($object, $key) use ($getter) {
            return (new GenericOrmCollection($getter, $getter))
                    ->setParent($object)->setParentKey($key);
        };


        $object = $this->newObjectWithRelations([], false, $relationLoader);

        $this->assertFalse($object->relatedLoaded('users'));
        $this->assertFalse($object->relatedLoaded('invoices'));

        $related = $object->invoices;

        $this->assertCount(3, iterator_to_array($related));

        $this->assertTrue($object->relatedLoaded('invoices'));

        $this->assertInstanceOf(OrmCollection::class, $related);
        $this->assertSame($object, $related->getParent());
        $this->assertEquals('invoices', $related->getParentKey());
    }

    public function newObject(array $attributes=[], $isFromStorage=false, callable $loader=null)
    {
        return new Address($attributes, $isFromStorage, $loader);
    }

    public function newObjectWithRelations(array $attributes=[], $isFromStorage=false, callable $loader=null)
    {
        return new AddressWithRelations($attributes, $isFromStorage, $loader);
    }
}

class Address extends OrmObject
{
    //
}

class Invoice extends OrmObject
{
    //
}

class OrmObjectTestUser extends OrmObject
{

}

class AddressWithRelations extends Address
{
    protected static function buildRelations()
    {
        $users = (new Relation())->setParent(new static)
                                 ->setParentKey('users')
                                 ->setRelatedObject(new OrmObjectTestUser())
                                 ->setBelongsToMany(false)
                                 ->setHasMany(false)
                                 ->setRequired(false)
                                 ->setParentRequired(true);

        $invoices = (new Relation())->setParent(new static)
                    ->setParentKey('invoices')
                    ->setRelatedObject(new Invoice())
                    ->setBelongsToMany(true)
                    ->setHasMany(true)
                    ->setRequired(false)
                    ->setParentRequired(true);

        return [
            $users->getParentKey() => $users,
            $invoices->getParentKey() => $invoices
        ];
    }
}
