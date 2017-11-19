<?php 

namespace Ems\Core\Collections;


class NestedArrayTest extends \Ems\TestCase
{
    public function testOffsetGetOnOneLevel()
    {
        $array = [
            'id'        => 13,
            'name'      => 'Michael',
            'surname'   => 'Tils'
        ];

        $grouper = $this->newGrouper($array);

        foreach ($array as $key=>$value) {
            $this->assertEquals($value, $grouper[$key]);
        }
    }

    public function testOffsetGetOnTwoLevels()
    {
        $array = [
            'id'            => 13,
            'name'          => 'Michael',
            'surname'       => 'Tils',
            'address.id'    => 578,
            'address.street'=> 'Elmstreet 13'
        ];

        $grouper = $this->newGrouper($array);

        foreach ($array as $key=>$value) {
            $this->assertEquals($value, $grouper[$key]);
        }
    }

    public function testOffsetGetGroupOnTwoLevels()
    {
        $array = [
            'id'            => 13,
            'name'          => 'Michael',
            'surname'       => 'Tils',
            'address.id'    => 578,
            'address.street'=> 'Elmstreet 13'
        ];

        $addressArray = [
            'id'     => 578,
            'street' => 'Elmstreet 13'
        ];

        $grouper = $this->newGrouper($array);

        $this->assertEquals($addressArray, $grouper['address']);
    }

    public function testOffsetGetDoesNotDestroyIndexedArrays()
    {
        $array = [
            'id'            => 13,
            'name'          => 'Michael',
            'surname'       => 'Tils',
            'address.id'    => 578,
            'address.street'=> 'Elmstreet 13',
            'address.lang' => ['en', 'de', 'fr']
        ];

        $addressArray = [
            'id'     => 578,
            'street' => 'Elmstreet 13',
            'lang'   => ['en', 'de', 'fr']
        ];

        $grouper = $this->newGrouper($array);

        $this->assertEquals($addressArray, $grouper['address']);
    }

    public function testLeadingSeparatorDoesntProduceChildren()
    {
        $array = [
            'id'            => 13,
            'name'          => 'Michael',
            'surname'       => 'Tils',
            '.id'           => 578,
            'address.street'=> 'Elmstreet 13'
        ];

        $grouper = $this->newGrouper($array);

        $resultArray = [
            'id'            => 13,
            'name'          => 'Michael',
            'surname'       => 'Tils',
            '.id'           => 578,
            'address'       => [
                'street' => 'Elmstreet 13'
            ]
        ];

        $this->assertEquals($resultArray, $grouper->nested());
    }

    public function testTrailingSeparatorDoesntProduceChildren()
    {
        $array = [
            'id'            => 13,
            'name'          => 'Michael',
            'surname'       => 'Tils',
            'id.'           => 578,
            'address.street'=> 'Elmstreet 13'
        ];

        $grouper = $this->newGrouper($array);

        $resultArray = [
            'id'            => 13,
            'name'          => 'Michael',
            'surname'       => 'Tils',
            'id.'           => 578,
            'address'       => [
                'street' => 'Elmstreet 13'
            ]
        ];

        $this->assertEquals($resultArray, $grouper->nested());
    }

    public function testLeadingAndTrailingSeparatorsDontProduceChildren()
    {
        $array = [
            'id'            => 13,
            'name'          => 'Michael',
            'surname'       => 'Tils',
            '.id.'          => 578,
            'address.street'=> 'Elmstreet 13'
        ];

        $grouper = $this->newGrouper($array);

        $resultArray = [
            'id'            => 13,
            'name'          => 'Michael',
            'surname'       => 'Tils',
            '.id.'          => 578,
            'address'       => [
                'street' => 'Elmstreet 13'
            ]
        ];

        $this->assertEquals($resultArray, $grouper->nested());
    }

    public function testOffsetGetGroupOnThreeLevels()
    {
        $array = [
            'id'                    => 13,
            'name'                  => 'Michael',
            'surname'               => 'Tils',
            'address.id'            => 578,
            'address.street'        => 'Elmstreet 13',
            'category.parent.id'    => 27,
            'category.parent.name'  => 'worker',
            'category.type.name'    => 'job'
        ];

        $categoryArray = [
            'id'     => 27,
            'name'   => 'worker'
        ];

        $grouper = $this->newGrouper($array);

        $this->assertEquals($categoryArray, $grouper['category.parent']);
    }

    public function testToNestedRemovesDirectLeaf()
    {
        $array = [
            'id'                    => 13,
            'name'                  => 'Michael',
            'surname'               => 'Tils',
            'address.id'            => 578,
            'address.street'        => 'Elmstreet 13',
            'category.parent.id'    => 27,
            'category.parent.name'  => 'worker',
            'category.type.name'    => 'job'
        ];

        $invalidArray = [
            'id'                    => 13,
            'name'                  => 'Michael',
            'surname'               => 'Tils',
            'address'               => 'foo',
            'address.id'            => 578,
            'address.street'        => 'Elmstreet 13',
            'category.parent.id'    => 27,
            'category.parent.name'  => 'worker',
            'category.type.name'    => 'job'
        ];


        $grouper = $this->newGrouper($array);

        $nested = $grouper->nested();

        $grouper2 = $this->newGrouper($invalidArray);

        $nested2 = $grouper2->nested();

        $this->assertEquals($nested, $nested2);
    }

    public function testRootReturnsOnlyDirectKeys()
    {
        $array = [
            'id'                    => 13,
            'name'                  => 'Michael',
            'surname'               => 'Tils',
            'address.id'            => 578,
            'address.street'        => 'Elmstreet 13',
            'category.parent.id'    => 27,
            'category.parent.name'  => 'worker',
            'age'                   => 86,
        ];

        $root = [
            'id'                    => 13,
            'name'                  => 'Michael',
            'surname'               => 'Tils',
            'age'                   => 86,
        ];

        $grouper = $this->newGrouper($array);

        $this->assertEquals($root, $grouper->root());

        $this->assertEquals($root, $grouper['.']);
    }

    public function testLeadingQuerySeparatorReturnsRootOfResult()
    {
        $array = [
            'id'                    => 13,
            'name'                  => 'Michael',
            'surname'               => 'Tils',
            'address.id'            => 578,
            'address.street'        => 'Elmstreet 13',
            'category.id'           => 83,
            'category.name'         => 'delivery',
            'category.parent.id'    => 27,
            'category.parent.name'  => 'worker',
            'age'                   => 86,
        ];

        $categoryRoot = [
            'id'                    => 83,
            'name'                  => 'delivery'
        ];

        $grouper = $this->newGrouper($array);

        $this->assertEquals($categoryRoot, $grouper['category.'] );
    }

    public function testOffsetGetWithDifferentQuerySeparator()
    {
        $array = [
            'id'                       => 13,
            'name'                     => 'Michael',
            'surname'                  => 'Tils',
            'address__id'              => 578,
            'address__street'          => 'Elmstreet 13',
            'category__parent__id'     => 27,
            'category__parent__name'   => 'worker',
            'category__type__name'     => 'job'
        ];

        $addressArray = [
            'id'       => 578,
            'street'   => 'Elmstreet 13'
        ];

        $categoryArray = [
            'id'     => 27,
            'name'   => 'worker'
        ];

        $typeArray = [
            'name'     => 'job'
        ];

        $grouper = $this->newGrouper($array, '__');

        $this->assertEquals($addressArray, $grouper['address']);
        $this->assertEquals($categoryArray, $grouper['category.parent']);
        $this->assertEquals($typeArray, $grouper['category.type']);
    }

    public function testOffsetExistsWorksWithNested()
    {
        $array = [
            'id'                    => 13,
            'name'                  => 'Michael',
            'surname'               => 'Tils',
            'address.id'            => 578,
            'address.street'        => 'Elmstreet 13',
            'category.parent.id'    => 27,
            'category.parent.name'  => 'worker',
            'age'                   => 86,
        ];

        $grouper = $this->newGrouper($array);

        foreach ($array as $key=>$value) {
            $this->assertTrue(isset($grouper[$key]));
        }

        $this->assertTrue(isset($grouper['address']));
        $this->assertFalse(isset($grouper['addressi']));
        $this->assertTrue(isset($grouper['address']['id']));
        $this->assertTrue(isset($grouper['category']));
        $this->assertFalse(isset($grouper['category']['id']));
        $this->assertTrue(isset($grouper['category']['parent']));
    }


    public function testOffsetExistsWithDotReturnsTrueOnNestedArray()
    {
        $array = [];

        $grouper = $this->newGrouper($array);

        $this->assertTrue(isset($grouper['.']));
    }

    public function testCountReturnsRootCount()
    {
        $array = [
            'id'                    => 13,
            'name'                  => 'Michael',
            'surname'               => 'Tils',
            'address.id'            => 578,
            'address.street'        => 'Elmstreet 13',
            'category.parent.id'    => 27,
            'category.parent.name'  => 'worker',
            'age'                   => 86,
        ];

        $grouper = $this->newGrouper($array);

        $this->assertEquals(4, count($grouper));
    }

    /**
     * @expectedException \RuntimeException
     **/
    public function testSetKeyThrowsException()
    {
        $grouper = $this->newGrouper([]);
        $grouper['bla'] = 'blub';
    }

    /**
     * @expectedException \RuntimeException
     **/
    public function testUnsetKeyThrowsException()
    {
        $grouper = $this->newGrouper([]);
        unset($grouper['bla']);
    }

    public function testIterateReturnsSameValues()
    {
        $array = [
            'id'                    => 13,
            'name'                  => 'Michael',
            'surname'               => 'Tils',
            'address.id'            => 578,
            'address.street'        => 'Elmstreet 13',
            'category.parent.id'    => 27,
            'category.parent.name'  => 'worker',
            'age'                   => 86,
        ];

        $grouper = $this->newGrouper($array);

        $nested = NestedArray::toNested($array);

        foreach ($grouper as $key=>$value) {
            $this->assertEquals($nested[$key], $value);
        }
    }

    public function testSubReturnsInstanceWithSubArray()
    {
        $array = [
            'id'                    => 13,
            'name'                  => 'Michael',
            'surname'               => 'Tils',
            'address.id'            => 578,
            'address.street'        => 'Elmstreet 13',
            'category.parent.id'    => 27,
            'category.parent.name'  => 'worker',
            'age'                   => 86,
        ];

        $grouper = $this->newGrouper($array)->sub('address');

        $this->assertInstanceOf(get_class($this->newGrouper()), $grouper);

        $this->assertCount(2, $grouper);
        $this->assertEquals($array['address.id'], $grouper['id']);
        $this->assertEquals($array['address.street'], $grouper['street']);
    }

    public function testInvokeReturnsSameAsSub()
    {
        $array = [
            'id'                    => 13,
            'name'                  => 'Michael',
            'surname'               => 'Tils',
            'address.id'            => 578,
            'address.street'        => 'Elmstreet 13',
            'category.parent.id'    => 27,
            'category.parent.name'  => 'worker',
            'age'                   => 86,
        ];


        $grouper = $this->newGrouper($array)->sub('category')->sub('parent');
        $root = $this->newGrouper($array);

        $this->assertCount(2, $grouper);
        $this->assertEquals($root('category.parent')->root(), $grouper->root());
        $this->assertEquals($root('.')->root(), $root->root());
    }

    public function testSubReturnsEmptyInstanceIfEmptyArray()
    {
        $array = [];

        $grouper = $this->newGrouper($array)->sub('address');

        $this->assertInstanceOf(get_class($this->newGrouper()), $grouper);

        $this->assertEquals(0, count($grouper));
        $this->assertEquals([], $grouper->getSrc());
    }

    public function testEmptyArray()
    {
        $array = [];

        $grouper = $this->newGrouper($array);

        $this->assertNull($grouper['address']);
        $this->assertFalse(isset($grouper['address']));
        $this->assertEquals([], $grouper['.']);
    }

    protected function newGrouper($array=[], $sep='.')
    {
        return new NestedArray($array, $sep);
    }
}
