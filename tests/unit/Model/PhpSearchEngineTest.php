<?php

namespace Ems\Model;

use Ems\Contracts\Core\Extractor as ExtractorContract;
use Ems\Contracts\Expression\ConditionGroup as ConditionGroupContract;
use Ems\Contracts\Model\Result;
use Ems\Contracts\Model\SearchEngine;
use Ems\Core\Collections\StringList;
use Ems\Core\Filesystem\CsvContent;
use Ems\Core\Filesystem\FileStream;
use Ems\Expression\Matcher;
use Ems\TestCase;
use Ems\TestData;
use function iterator_to_array;

class PhpSearchEngineTest extends TestCase
{
    use TestData;

    /**
     * @var array
     */
    protected static $items = [];

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            SearchEngine::class,
            $this->newEngine()
        );

    }

    public function test_newQuery_returns_empty_query()
    {
        $engine = $this->newEngine();

        $query = $engine->newQuery();
        $this->assertInstanceOf(ConditionGroupContract::class, $query);
        $this->assertCount(0, $query->expressions());
    }

    public function test_getData_and_setData()
    {
        $provider = function() { return ['a', 'b']; };
        $engine = $this->newEngine();
        $this->assertSame($engine, $engine->provideDataBy($provider));
        $this->assertEquals(['a', 'b'], $engine->getData());
        $this->assertSame($engine, $engine->setData(['b', 'c']));
        $this->assertEquals(['b', 'c'], $engine->getData());
    }

    public function test_search_without_filter()
    {
        $engine = $this->newEngine();
        $query = $engine->newQuery();

        $result = $engine->search($query);

        $this->assertInstanceOf(Result::class, $result);
        $items = iterator_to_array($result);

        $this->assertCount(268, $items);
    }

    public function test_paginate_without_filter()
    {
        $engine = $this->newEngine();
        $query = $engine->newQuery();

        $result = $engine->search($query);

        $this->assertInstanceOf(Result::class, $result);
        $items = iterator_to_array($result->paginate(1, 15));
        $this->assertCount(15, $items);

        $this->assertEquals('Afghanistan', $items[0]->name);
        $this->assertEquals('Barbados', $items[14]->name);

        $items = iterator_to_array($result->paginate(2, 15));

        $this->assertEquals('Belarus', $items[0]->name);
        $this->assertEquals('Cameroon', $items[14]->name);
    }

    public function test_search_with_simple_filter()
    {
        $engine = $this->newEngine();
        $query = $engine->newQuery()->where('name', 'Chad');

        $result = $engine->search($query);

        $this->assertInstanceOf(Result::class, $result);
        $items = iterator_to_array($result);

        $this->assertCount(1, $items);
        $this->assertEquals('Chad', $items[0]->name);
    }

    public function test_search_with_simple_filters()
    {
        $engine = $this->newEngine();
        $query = $engine->newQuery()->where('id', '>=', 20)
                                    ->where('id', '<=', 39);

        $result = $engine->search($query);

        $this->assertInstanceOf(Result::class, $result);
        $items = iterator_to_array($result);

        $this->assertCount(20, $items);
        $this->assertEquals(20, $items[0]->getId());
        $this->assertEquals(39, $items[19]->getId());
    }

    public function test_paginate_with_simple_filters()
    {
        $engine = $this->newEngine();
        $query = $engine->newQuery()->where('id', '>=', 20)
            ->where('id', '<=', 39);

        $result = $engine->search($query);

        $this->assertInstanceOf(Result::class, $result);
        $items = iterator_to_array($result->paginate(1, 10));

        $this->assertCount(10, $items);
        $this->assertEquals(20, $items[0]->getId());
        $this->assertEquals(29, $items[9]->getId());

        $items = iterator_to_array($result->paginate(2, 10));

        $this->assertCount(10, $items);
        $this->assertEquals(30, $items[0]->getId());
        $this->assertEquals(39, $items[9]->getId());

    }

    public function test_search_with_filters_of_relations()
    {
        $engine = $this->newEngine();
        $query = $engine->newQuery()->where('capital.name', 'like', 'San%');

        $result = $engine->search($query);

        $this->assertInstanceOf(Result::class, $result);

        $items = iterator_to_array($result);

        $this->assertCount(7, $items);

        foreach ($result as $item) {
            $this->assertStringStartsWith('San', $item->capital->name);
        }

    }

    public function test_sort_numeric_in_unfiltered_result()
    {
        $engine = $this->newEngine();
        $query = $engine->newQuery();

        $result = $engine->search($query, ['id' => 'desc']);

        $items = iterator_to_array($result);

        //$this->assertCount(268, $items);

        $this->assertEquals('British Antarctic Territory', $items[0]->name);
        $this->assertEquals('Afghanistan', $items[267]->name);
    }

    public function test_sort_boolean_in_unfiltered_result()
    {
        $engine = $this->newEngine();
        $query = $engine->newQuery();

        $result = $engine->search($query, ['has_capital' => 'desc']);

        $items = iterator_to_array($result);

        //$this->assertCount(268, $items);

        $this->assertTrue($items[0]->has_capital);
        $this->assertFalse($items[267]->has_capital);
    }

    public function test_sort_string_in_filtered_result()
    {
        $engine = $this->newEngine();
        $query = $engine->newQuery()->where('capital.name', '<>', '');

        $result = $engine->search($query, ['capital.name' => 'asc']);

        $items = iterator_to_array($result);

        $this->assertCount(245, $items);

        $this->assertEquals('United Arab Emirates', $items[0]->name);
        $this->assertEquals('Croatia', $items[244]->name);
    }

    public function test_sort_multiple_in_unfiltered_result()
    {
        $engine = $this->newEngine();
        $query = $engine->newQuery()->where('currency.code', '<>', '');

        $result = $engine->search($query, ['currency.code' => 'desc', 'name' => 'asc']);

        $items = iterator_to_array($result->paginate(1, 20));

        $this->assertCount(20, $items);

        $this->assertEquals('Zimbabwe', $items[0]->name);
        $this->assertEquals('Montserrat', $items[19]->name);


    }

    public function test_paginate_multiple_in_unfiltered_result()
    {
        $engine = $this->newEngine();
        $query = $engine->newQuery();

        $result = $engine->search($query, ['type' => 'asc', 'name' => 'desc']);

        $items = iterator_to_array($result);

        $this->assertCount(268, $items);

        $this->assertEquals('Antarctic Territory', $items[0]->type);
        $this->assertEquals('Ross Dependency', $items[0]->name);
        $this->assertEquals('Proto Independent State', $items[267]->type);
        $this->assertEquals('Abkhazia', $items[267]->name);

    }

    protected function newEngine(Matcher $matcher=null, ExtractorContract $extractor=null)
    {
        $matcher = $matcher ?: $this->newMatcher();
        $engine = new PhpSearchEngine($matcher, $extractor);
        $engine->provideDataBy([$this, 'getItems']);
        return $engine;
    }

    protected function newMatcher()
    {
        return new Matcher();
    }

    public function getItems()
    {
        if (static::$items) {
            return static::$items;
        }

        static::$items = [];

        $rows = new CsvContent(new FileStream(static::dataFile('Countries-ISO-3166-2.csv')));
        //$rows->setUrl(new Url(static::dataFile('Countries-ISO-3166-2.csv')));

        /** @var array $row */
        foreach ($rows->rows() as $row) {
            $ormData = [
                'id'            => $row['Sort Order'],
                'name'          => $row['Common Name'],
                'formal_name'   => $row['Formal Name'],
                'capital_name'  => $row['Capital'],
                'iso_code'      => $row['ISO 3166-1 2 Letter Code'],
                'iso_code3'     => $row['ISO 3166-1 3 Letter Code'],
                'currency_code' => $row['ISO 4217 Currency Code'],
                'currency_name' => $row['ISO 4217 Currency Name'],
                'phone_code'    => $row['ITU-T Telephone Code'],
                'type'          => $row['Type'],
                'has_capital'   => (bool)$row['Capital']
            ];

            static::$items[] = new PhpSearchEngineTest_Country($ormData, true, function($obj, $key) {
                return $this->loadRelation($obj, $key);
            });

        }

        return static::$items;

    }

    /**
     * @param OrmObject $object
     * @param $key
     *
     * @return mixed
     */
    public function loadRelation(OrmObject $object, $key)
    {
        if ($key == 'capital') {
            return new PhpSearchEngineTest_Capital(['name' => $object->capital_name], true);
        }

        if ($key == 'currency') {
            return new PhpSearchEngineTest_Currency([
                'code' => $object->currency_code,
                'name' => $object->currency_name
            ], true);
        }

        if ($key == 'iso_codes') {
            $codes = [
                new PhpSearchEngineTest_ISOCode(['type' => 'ISO 3166-1 2', 'code' => $object->iso_code]),
                new PhpSearchEngineTest_ISOCode(['type' => 'ISO 3166-1 3', 'code' => $object->iso_code3]),
                new PhpSearchEngineTest_ISOCode(['type' => 'ISO 4217',     'code' => $object->currency_code]),
                new PhpSearchEngineTest_ISOCode(['type' => 'ITU-T',        'code' => $object->phone_code])
            ];

            return (new GenericOrmCollection($codes))
                ->setOrmObject(new PhpSearchEngineTest_ISOCode())
                ->setParentKey('iso_codes')
                ->setParent($object);
        }
    }

}

class PhpSearchEngineTest_ISOCode extends OrmObject
{

}

class PhpSearchEngineTest_Capital extends OrmObject
{

}

class PhpSearchEngineTest_Currency extends OrmObject
{

}

class PhpSearchEngineTest_Country extends OrmObject
{

    public function keys()
    {
        return new StringList(['id', 'login', 'last_login', 'age', 'created_at', 'categories']);
    }

    protected static function buildRelations()
    {
        $capital = (new Relation())->setParent(new static)
            ->setParentKey('capital')
            ->setRelatedObject(new PhpSearchEngineTest_Capital())
            ->setBelongsToMany(false)
            ->setHasMany(false)
            ->setRequired(false)
            ->setParentRequired(true);

        $currency = (new Relation())->setParent(new static)
            ->setParentKey('currency')
            ->setRelatedObject(new PhpSearchEngineTest_Currency())
            ->setBelongsToMany(true)
            ->setHasMany(false)
            ->setRequired(true)
            ->setParentRequired(false);

        $isoCodes = (new Relation())->setParent(new static)
            ->setParentKey('iso_codes')
            ->setRelatedObject(new PhpSearchEngineTest_ISOCode())
            ->setBelongsToMany(true)
            ->setHasMany(true)
            ->setRequired(true)
            ->setParentRequired(false);

        return [
            $capital->getParentKey()  => $capital,
            $currency->getParentKey() => $currency,
            $isoCodes->getParentKey() => $isoCodes
        ];
    }
}