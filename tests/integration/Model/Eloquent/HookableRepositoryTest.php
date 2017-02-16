<?php

namespace Ems\Model\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Ems\Contracts\Model\HookableRepository as HookableRepositoryContract;
use Ems\Testing\Eloquent\MigratedDatabase;
use Ems\Core\NamedObject;

class HookableRepositoryTest extends \Ems\TestCase
{
    use MigratedDatabase;

    public function test_implements_interface()
    {
        $this->assertInstanceOf(HookableRepositoryContract::class, $this->newRepo());
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_instantiating_with_unidentifiable_throws_InvalidArgumentException()
    {
        $this->newRepo(new UnIdentifiable);
    }

    public function test_get_returns_default()
    {
        $repo = $this->newRepo();
        $this->assertEquals('foo', $repo->get(12, 'foo'));
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\NotFound
     **/
    public function test_getOrFail_throws_NotFound_if_model_not_found()
    {
        $repo = $this->newRepo();
        $this->assertEquals('foo', $repo->getOrFail(12));
    }

    public function test_make_returns_filled_model()
    {
        $repo = $this->newRepo();

        $attributes = ['login' => 'michael', 'email'=>'info@mydomain.de'];

        $model = $repo->make($attributes);

        $this->assertInstanceOf(User::class, $model);
        $this->assertEquals($attributes, $model->getAttributes());

    }

    public function test_store_stores_model_in_database()
    {
        $repo = $this->newRepo();

        $attributes = [
            'login'     => 'michael',
            'email'     => 'michael@ems.org',
            'password'  => 'test1234'
        ];

        $model = $repo->store($attributes);

        $this->assertInstanceOf(User::class, $model);
        foreach ($attributes as $key=>$value) {
            $this->assertEquals($value, $model->getAttribute($key));
        }
        $this->assertTrue($model->getKey() > 0);

    }

    public function test_getOrFail_returns_stored_model()
    {
        $repo = $this->newRepo();

        $attributes = [
            'login'     => 'sandra',
            'email'     => 'sandra@ems.org',
            'password'  => 'test1234'
        ];

        $modelId = $repo->store($attributes)->getKey();

        $model = $repo->getOrFail($modelId);

        $this->assertInstanceOf(User::class, $model);
        foreach ($attributes as $key=>$value) {
            $this->assertEquals($value, $model->getAttribute($key));
        }
        $this->assertEquals($modelId, $model->getKey());

    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_update_fails_with_non_eloquent_model()
    {
        $repo = $this->newRepo();

        $model = new NamedObject;

        $repo->update($model, ['login' => 'nash']);

    }

    public function test_update_does_not_update_model_if_attributes_did_not_change()
    {
        $repo = $this->newRepo();

        $attributes = [
            'login'     => 'sarah',
            'email'     => 'sarah@ems.org',
            'password'  => 'test1234'
        ];

        $modelId = $repo->store($attributes)->getKey();

        $model = $repo->getOrFail($modelId);

        $saveCounter = $model->saveCounter;

        $this->assertFalse($repo->update($model, $attributes));

        $this->assertInstanceOf(User::class, $model);
        foreach ($attributes as $key=>$value) {
            $this->assertEquals($value, $model->getAttribute($key));
        }

        $this->assertEquals($saveCounter, $model->saveCounter);

    }

    public function test_update_does_update_model_if_attributes_did_change()
    {
        $repo = $this->newRepo();

        $attributes = [
            'login'     => 'georg',
            'email'     => 'georg@ems.org',
            'password'  => 'test1234'
        ];

        $modelId = $repo->store($attributes)->getKey();

        $model = $repo->getOrFail($modelId);

        $saveCounter = $model->saveCounter;

        $newAttributes = [
            'login'     => 'billy',
            'email'     => 'billy@ems.org',
            'password'  => 'test1234'
        ];

        $this->assertTrue($repo->update($model, $newAttributes));

        foreach ($newAttributes as $key=>$value) {
            $this->assertEquals($value, $model->getAttribute($key));
        }


        $this->assertGreaterThan($saveCounter, $model->saveCounter);

    }

    public function test_delete_deletes_stored_model()
    {
        $repo = $this->newRepo();

        $attributes = [
            'login'     => 'jill',
            'email'     => 'jill@ems.org',
            'password'  => 'test1234'
        ];

        $model = $repo->store($attributes);

        $copy = $repo->getOrFail($model->id);

        $this->assertEquals($model->id, $copy->id);

        $repo->delete($copy);

        $this->assertNull($repo->get($model->id));

    }

    public function test_methodHooks_return_all_needed_hooks()
    {
        $needed = ['get', 'make', 'store', 'fill', 'update', 'save', 'delete'];
        $hooks = $this->newRepo()->methodHooks();

        foreach ($needed as $hook) {
            $this->assertTrue(in_array($hook, $hooks));
        }
    }

    public function test_fill_filters_nonscalar_attributes()
    {

        $repo = $this->newRepo();

        $attributes = [
            'login'     => 'jill',
            'email'     => 'jill@ems.org',
            'password'  => 'test1234',
            'permissions' => ['cms.access'=>1]
        ];

        $model = $repo->make($attributes);

        foreach ($attributes as $key=>$value) {
            if ($key == 'permissions') {
                $this->assertFalse($model->__isset($key));
                continue;
            }
            $this->assertEquals($value, $model->getAttribute($key));
        }
    }

    public function test_fill_does_not_filter_nonscalar_attributes_if_marked()
    {

        $repo = $this->newRepo();

        $repo->setNonScalarAttributes('permissions');

        $attributes = [
            'login'     => 'jill',
            'email'     => 'jill@ems.org',
            'password'  => 'test1234',
            'permissions' => ['cms.access'=>1]
        ];

        $model = $repo->make($attributes);

        foreach ($attributes as $key=>$value) {
            $this->assertEquals($value, $model->getAttribute($key));
        }
    }

    public function test_fill_filters_empty_strings_if_key_looks_like_a_foreign_key()
    {

        $repo = $this->newRepo();

        $attributes = [
            'login'     => 'jill',
            'email'     => 'jill@ems.org',
            'password'  => 'test1234',
            'contact_id' => ''
        ];

        $model = $repo->make($attributes);

        foreach ($attributes as $key=>$value) {
            if ($key == 'contact_id') {
                $this->assertFalse($model->__isset($key));
                continue;
            }
            $this->assertEquals($value, $model->getAttribute($key));
        }
    }

    public function test_fill_uses_custom_attribute_filter()
    {

        $repo = $this->newRepo();

        $attributes = [
            'login'     => 'jill',
            'email'     => 'jill@ems.org',
            'password'  => 'test1234'
        ];

        $repo->filterAttributesBy(function ($key, $value) {
            return $key != 'password';
        });

        $model = $repo->make($attributes);

        foreach ($attributes as $key=>$value) {
            if ($key == 'password') {
                $this->assertFalse($model->__isset($key));
                continue;
            }
            $this->assertEquals($value, $model->getAttribute($key));
        }
    }

    protected function newRepo(EloquentModel $model=null)
    {
        return new HookableRepository($model ?: new User);
    }

}

class User extends Model
{

    public $saveCounter = 0;

    protected $guarded = ['id'];

    protected $casts = ['blob'=>'array'];

    /**
     * {@inheritdoc}
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        $this->saveCounter++;
        return parent::save($options);
    }

}

class UnIdentifiable extends EloquentModel
{
}


