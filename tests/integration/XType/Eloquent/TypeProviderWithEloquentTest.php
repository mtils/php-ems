<?php


namespace Ems\XType\Eloquent;

require_once(__DIR__.'/ModelTypeFactoryTest.php'); // Load fake models

use Ems\AppTrait;
use Ems\Contracts\XType\TypeProvider as TypeProviderContract;
use Ems\XType\Eloquent\ModelTypeFactory;
use Ems\XType\TemporalType;
use Ems\XType\StringType;


class TypeProviderWithEloquentTest extends ModelTypeFactoryTest
{

    /**
     * Skip base test because provider supports it
     **/
    public function test_toType_throws_Unsupported_if_class_not_an_eloquent_model()
    {
    }

    public function test_key_access()
    {
        $type = $this->xType(PlainUser::class, 'updated_at');
        $this->assertInstanceOf(TemporalType::class, $type);
        $this->assertTrue($type->readonly);
    }

    public function test_dotted_key_access()
    {
        $type = $this->xType(User::class, 'orders.comments.comment');
        $this->assertInstanceOf(StringType::class, $type);
        $this->assertEquals(5, $type->min);
        $this->assertEquals(255, $type->max);

    }

    protected function xType($model, $path=null)
    {
        return $this->app(TypeProviderContract::class)->xType($model, $path);
    }

}
