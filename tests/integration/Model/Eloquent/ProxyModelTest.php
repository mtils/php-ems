<?php
/**
 *  * Created by mtils on 19.12.19 at 13:22.
 **/

namespace Ems\Model\Eloquent;

use DateTime;
use Ems\Model\Eloquent\Contracts\ProxyModel;
use Ems\TestCase;
use Ems\Testing\Eloquent\MigratedDatabase;

class ProxyModelTest extends TestCase
{
    use MigratedDatabase;

    /**
     * @test
     */
    public function it_implements_interface_by_trait()
    {
        /** The ProxyModel interface is what your repository uses internally */
        $this->assertInstanceOf(ProxyModel::class, $this->newProxy());

        /** The ProxyModelTest_User is the interface that is shown to the outside */
        $this->assertInstanceOf(ProxyModelTest_User::class, $this->newProxy());
        $this->assertInstanceOf(ProxyModelTest_User::class, new ProxyModelTest_StdClass_User());
    }

    /**
     * @test
     */
    public function it_allows_to_assign_model()
    {
        $proxy = $this->newProxy();
        $user = $this->newModel();
        $this->assertSame($proxy, $proxy->setModel($user));
        $this->assertSame($user, $proxy->getModel());
    }

    /**
     * @test
     */
    public function it_forwards_isset_to_model()
    {
        $proxy = $this->newProxy($this->newModel());
        $this->assertFalse(isset($proxy->login));
        $proxy->getModel()->setAttribute('login', 'foo');
        $this->assertTrue(isset($proxy->login));
    }

    /**
     * @test
     */
    public function it_forwards_get_to_model()
    {
        $proxy = $this->newProxy($this->newModel());
        $this->assertNull($proxy->login);
        $proxy->getModel()->setAttribute('login', 'foo');
        $this->assertEquals('foo', $proxy->login);
    }

    /**
     * @test
     */
    public function it_forwards_set_to_model()
    {
        $proxy = $this->newProxy($this->newModel());
        $this->assertNull($proxy->login);

        $proxy->login = 'foo';
        $this->assertEquals('foo', $proxy->login);
    }

    /**
     * @test
     */
    public function it_forwards_unset_to_model()
    {
        $proxy = $this->newProxy($this->newModel());
        $this->assertFalse(isset($proxy->login));
        $proxy->getModel()->setAttribute('login', 'foo');
        $this->assertTrue(isset($proxy->login));
        unset($proxy->login);
        $this->assertFalse(isset($proxy->login));
    }

    protected function newModel($attributes=[])
    {
        return new ProxyModelTest_UserModel($attributes);
    }

    protected function newProxy(Model $model=null)
    {
        $proxy = new ProxyModelTest_EloquentProxy();
        return $model ? $proxy->setModel($model) : $proxy;
    }
}

class ProxyModelTest_UserModel extends Model
{
    protected $table = 'users';
}

/**
 * Class ProxyModelTest_User
 *
 * This is your "value object interface". It just tells your IDE that the
 * properties are in this object.
 *
 * @package Ems\Model\Eloquent
 *
 * @property int    id
 * @property string login
 * @property string email
 * @property string locale
 * @property DateTime created_at
 * @property DateTime updated_at
 */
abstract class ProxyModelTest_User
{
    //
}

class ProxyModelTest_StdClass_User extends ProxyModelTest_User
{
    public $id = 0;
    public $login = '';
    public $email = '';
    public $locale = '';
    public $created_at;
    public $updated_at;
}

class ProxyModelTest_EloquentProxy extends ProxyModelTest_User implements ProxyModel
{
    use ProxyModelTrait;
}