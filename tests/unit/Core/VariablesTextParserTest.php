<?php

namespace Ems\Core;

use Illuminate\Database\Eloquent\Model;
use Mockery as m;
use stdClass;

class VariablesTextParserTest extends \Ems\TestCase
{
    public function testImplementsInterface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Core\TextParser',
            $this->newParser()
        );
    }

    public function testReplacesFirstLevelData()
    {
        $data = [
            'salutation'    => 'Hello',
            'name'          => 'Gaylord',
            'age'           => 33,
            'taxes'         => 0.19
        ];

        $parser = $this->newParser();

        $text = '{salutation} Mr. {name} since you are {age} you pay {taxes} taxes';

        $expected = 'Hello Mr. Gaylord since you are 33 you pay 0.19 taxes';

        $this->assertEquals($expected, $parser->parse($text, $data));
    }

    public function testReplacesNestedArraysWithUmlauts()
    {
        $data = [
            'address' => [
                'street' => [
                    'name' => 'Bängnösestraße'
                ],
                'location' => 'Bangkok'
            ],
            'name' => 'Wärner'
        ];

        $parser = $this->newParser();

        $text = 'Hallöö {name} schön daß Du in der {address.street.name} in {address.location} wohnst';

        $expected = 'Hallöö Wärner schön daß Du in der Bängnösestraße in Bangkok wohnst';

        $this->assertEquals($expected, $parser->parse($text, $data));
    }

    public function testReplacesNestedStdClassObjects()
    {
        $data = [];

        $data['address'] = new stdClass();
        $data['address']->street = new stdclass();
        $data['address']->street->name = 'Bängnösestraße';
        $data['address']->location = 'Bangkok';
        $data['name'] = 'Wärner';

        $parser = $this->newParser();

        $text = 'Hallöö {name} schön daß Du in der {address.street.name} in {address.location} wohnst';

        $expected = 'Hallöö Wärner schön daß Du in der Bängnösestraße in Bangkok wohnst';

        $this->assertEquals($expected, $parser->parse($text, $data));
    }

    public function testReplacesNestedEloquentObjects()
    {
        $data = [];

        $address = $this->newTestModel();
        $address->setAttribute('location', 'Bangkok');

        $street = $this->newTestModel();
        $street->setAttribute('name', 'Bängnösestraße');

        $address->setRelation('street', $street);

        $data['address'] = $address;

        $data['name'] = 'Wärner';

        $parser = $this->newParser();

        $text = 'Hallöö {name} schön daß Du in der {address.street.name} in {address.location} wohnst';

        $expected = 'Hallöö Wärner schön daß Du in der Bängnösestraße in Bangkok wohnst';

        $this->assertEquals($expected, $parser->parse($text, $data));
    }

    protected function newTestModel()
    {
        return new TestModel();
    }

    protected function newParser()
    {
        return new VariablesTextParser();
    }
}

class TestModel extends Model
{
}
