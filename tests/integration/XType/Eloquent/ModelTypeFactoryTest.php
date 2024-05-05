<?php


namespace Ems\XType\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ems\Contracts\XType\SelfExplanatory;
use Ems\Testing\Eloquent\MigratedDatabase;
use Ems\XType\TypeFactory;
use Ems\XType\TemporalType;
use Ems\XType\NumberType;
use Ems\XType\BoolType;
use Ems\XType\StringType;
use Ems\XType\ArrayAccessType;
use Ems\XType\ObjectType;
use Ems\XType\SequenceType;
use Ems\Model\Eloquent\EntityTrait;
use Ems\Contracts\Core\Entity;

use function var_dump;

require_once(__DIR__.'/test_models.php');


class ModelTypeFactoryTest extends \Ems\IntegrationTest
{
    use MigratedDatabase;

    protected $typeFactory;

    protected $typeProvider;

    public function test_implements_interface()
    {
        $this->assertInstanceOf(EloquentModel::class, new PlainUser);
    }

    public function test_myXtype_returns_basic_properties()
    {
        $type = $this->xType(new PlainUser);
        $this->assertInstanceOf(NumberType::class, $type['id']);
        $this->assertTrue($type['id']->readonly);

        foreach (['created_at','updated_at'] as $key) {
            $this->assertInstanceOf(TemporalType::class, $type[$key]);
            $this->assertTrue($type[$key]->readonly);
        }

        $type = $this->xType(new PlainUser);
        $this->assertInstanceOf(NumberType::class, $type['id']);
        $this->assertTrue($type['id']->readonly);

    }

    public function test_myXtype_returns_standard_properties()
    {
        $type = $this->xType(new StandardUser);

        $this->assertInstanceOf(NumberType::class, $type['id']);
        $this->assertTrue($type['id']->readonly);

        foreach (['login','email','password','first_name','last_name','mother'] as $key) {
            $this->assertInstanceOf(StringType::class, $type[$key]);
        }

        foreach (['created_at','updated_at','deleted_at','activated_at', 'last_login'] as $key) {
            $this->assertInstanceOf(TemporalType::class, $type[$key], "$key is no temporal type");
            if (!in_array($key, ['activated_at', 'last_login'])) {
                $this->assertTrue($type[$key]->readonly);
            }
        }

        $this->assertInstanceOf(NumberType::class, $type['category_id']);
        $this->assertEquals('int', $type['category_id']->nativeType);

        $this->assertInstanceOf(NumberType::class, $type['weight']);
        $this->assertEquals('float', $type['weight']->nativeType);

        $this->assertInstanceOf(BoolType::class, $type['is_banned']);

        $this->assertInstanceOf(ArrayAccessType::class, $type['permissions']);
        $this->assertInstanceOf(ObjectType::class, $type['acl']);
        $this->assertInstanceOf(ObjectType::class, $type['paw_patrol']);
        $this->assertEquals('Illuminate\Support\Collection', $type['paw_patrol']->class);

    }

    public function test_myXtype_returns_manual_properties()
    {
        $type = $this->xType(new User);

        $this->assertInstanceOf(NumberType::class, $type['id']);
        $this->assertTrue($type['id']->readonly);

        foreach (['created_at','updated_at'] as $key) {
            $this->assertInstanceOf(TemporalType::class, $type[$key]);
            $this->assertTrue($type[$key]->readonly);
        }

        $this->assertInstanceOf(StringType::class, $type['login']);

        $this->assertEquals(5, $type['login']->min);
        $this->assertEquals(128, $type['login']->max);

        $this->assertInstanceOf(StringType::class, $type['email']);
        $this->assertEquals(10, $type['email']->min);
        $this->assertEquals(255, $type['email']->max);


    }

    public function test_myXtype_returns_manual_and_standard_properties()
    {
        $type = $this->xType(new ExtendedUser);

        $this->assertInstanceOf(NumberType::class, $type['id']);
        $this->assertTrue($type['id']->readonly);

        foreach (['created_at','updated_at'] as $key) {
            $this->assertInstanceOf(TemporalType::class, $type[$key]);
            $this->assertTrue($type[$key]->readonly);
        }

        $this->assertInstanceOf(StringType::class, $type['login']);

        $this->assertEquals(5, $type['login']->min);
        $this->assertEquals(128, $type['login']->max);

        $this->assertInstanceOf(StringType::class, $type['email']);
        $this->assertEquals(10, $type['email']->min);
        $this->assertEquals(255, $type['email']->max);

        foreach (['created_at','updated_at','deleted_at'] as $key) {
            $this->assertInstanceOf(TemporalType::class, $type[$key]);
            $this->assertTrue($type[$key]->readonly);
        }
    }

    public function test_myXType_overwrites_auto_detected_types_with_manually_setted()
    {

        $type = $this->xType(new User);

        $this->assertInstanceOf(StringType::class, $type['password']);
        $this->assertEquals(6, $type['password']->min);
        $this->assertEquals(255, $type['password']->max);
        $this->assertTrue(isset($type['password']->notNull));

    }

    public function test_myXtype_parses_belongsTo_relation()
    {
        $type = $this->xType(new User);

        $categoryType = $type['category'];

        $this->assertInstanceOf(ObjectType::class, $categoryType);
        $this->assertEquals(Category::class, $categoryType->class);
        $this->assertInstanceOf(StringType::class, $categoryType['name']);
        $this->assertEquals(10, $categoryType['name']->min);


    }

    public function test_myXtype_parses_hasOne_relation()
    {
        $type = $this->xType(new User);

        $addressType = $type['address'];

        $this->assertInstanceOf(ObjectType::class, $addressType);
        $this->assertEquals(Address::class, $addressType->class);
        $this->assertInstanceOf(StringType::class, $addressType['street']);
        $this->assertEquals(10, $addressType['street']->min);
        $this->assertEquals(255, $addressType['street']->max);


    }

    public function test_myXtype_parses_hasMany_relation()
    {
        $type = $this->xType(new User);

        $ordersType = $type['orders'];

        $this->assertInstanceOf(SequenceType::class, $ordersType);

        $orderType = $ordersType->itemType;
        $this->assertEquals(Order::class, $orderType->class);
        $this->assertInstanceOf(StringType::class, $orderType['name']);
        $this->assertEquals(2, $orderType['name']->min);
        $this->assertEquals(255, $orderType['name']->max);


    }

    public function test_myXtype_parses_belongsToMany_relation()
    {
        $type = $this->xType(new User);

        $tagsType = $type['tags'];

        $this->assertInstanceOf(SequenceType::class, $tagsType);

        $tagType = $type['tags']->itemType;

        $this->assertEquals(Tag::class, $tagType->class);
        $this->assertInstanceOf(StringType::class, $tagType['name']);
        $this->assertEquals(2, $tagType['name']->min);
        $this->assertEquals(255, $tagType['name']->max);


    }

    public function test_myXtype_parses_hasManyThrough_relation()
    {
        $type = $this->xType(new Country);

        $residentsType = $type['residents'];

        $this->assertInstanceOf(SequenceType::class, $residentsType);

        $residentType = $residentsType->itemType;

        $this->assertEquals(User::class, $residentType->class);
        $this->assertInstanceOf(StringType::class, $residentType['login']);
        $this->assertEquals(5, $residentType['login']->min);
        $this->assertEquals(128, $residentType['login']->max);


    }

    public function test_myXtype_parses_morphOne_relation()
    {
        $type = $this->xType(new User);

        $noteType = $type['note'];

        $this->assertEquals(Note::class, $noteType->class);
        $this->assertInstanceOf(StringType::class, $noteType['note']);
        $this->assertEquals(5, $noteType['note']->min);
        $this->assertEquals(64000, $noteType['note']->max);


    }

    public function test_myXtype_parses_morphMany_relation()
    {
        $type = $this->xType(new Order);

        $commentsType = $type['comments'];

        $this->assertInstanceOf(SequenceType::class, $commentsType);

        $commentType = $commentsType->itemType;

        $this->assertEquals(Comment::class, $commentType->class);
        $this->assertInstanceOf(StringType::class, $commentType['comment']);
        $this->assertEquals(5, $commentType['comment']->min);
        $this->assertEquals(255, $commentType['comment']->max);


    }

    public function test_XTypeTrait_caches_result_per_class()
    {
        $order = new Order;
        $type = $this->xType($order);

        $this->assertEquals(1, $order->getBootCount());

        $type = $this->xType($order);
        $this->assertEquals(1, $order->getBootCount());

    }

    public function test_myXtype_throws_exception_ConfigurationError_if_itemType_not_configured()
    {
        $this->expectException(
            \Ems\Contracts\Core\Errors\ConfigurationError::class
        );
        $numbersType = $this->xType(new AdditionalPhoneNumbers)['phone_numbers'];

        $itemType = $emailsType->itemType;

        $this->assertInstanceOf(SequenceType::class, $emailsType);

        $this->assertInstanceOf(StringType::class, $itemType);
        $this->assertEquals(10, $itemType->min);
        $this->assertEquals(128, $itemType->max);


    }

    public function test_myXtype_parses_sequenceType_with_string_items()
    {
        $emailsType = $this->xType(new AdditionalEmails)['emails'];

        $itemType = $emailsType->itemType;

        $this->assertInstanceOf(SequenceType::class, $emailsType);

        $this->assertInstanceOf(StringType::class, $itemType);
        $this->assertEquals(10, $itemType->min);
        $this->assertEquals(128, $itemType->max);


    }

    public function test_toType_throws_Unsupported_if_class_not_an_eloquent_model()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\Unsupported::class);
        $this->xType(new \stdClass);
    }

    public function test_toType_throws_Unsupported_if_relation_is_no_eloquent_relation()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\Unsupported::class);
        $this->xType(new WrongRelationType);
    }

    protected function xType($model, $path=null)
    {
        return $this->provider()->toType($model);
    }

    protected function newUser()
    {
        return new PlainUser;
    }

    protected function factory()
    {
        if (!$this->typeFactory) {
            $this->typeFactory = new TypeFactory;
        }
        return $this->typeFactory;
    }

    protected function provider()
    {
        if (!$this->typeProvider) {
            $this->typeProvider = $this->app(ModelTypeFactory::class);
        }
        return $this->typeProvider;
    }
}


