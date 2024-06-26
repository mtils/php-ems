<?php

namespace Ems\Model;

use ArrayIterator;
use Ems\Contracts\Model\OrmObject as OrmObjectContract;
use Ems\Contracts\Model\Search as SearchContract;
use Ems\Contracts\Model\SearchEngine;
use Ems\Contracts\Pagination\Paginator;
use Ems\Core\Collections\StringList;
use Ems\Expression\ConditionGroup;
use Ems\TestCase;
use Ems\Testing\Cheat;
use Ems\Testing\LoggingCallable;
use function func_get_args;
use Mockery;
use function array_slice;
use function iterator_to_array;

class GenericSearchTest extends TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            SearchContract::class,
            $this->newSearch()
        );

    }

    public function test_pass_OrmObject_in___construct()
    {
        $search = $this->newSearch(null, new GenericSearchTest_OrmObject());
        $this->assertInstanceOf(GenericSearchTest_OrmObject::class, $search->ormObject());
    }

    public function test_pass_array_result_in___construct()
    {

        $data = $this->getTestData();

        $search = $this->newSearch($data, new GenericSearchTest_OrmObject());
        $this->assertEquals($data, iterator_to_array($search));
        $this->assertEquals($search->getResult(), $data);
    }

    public function test_pass_traversable_result_in___construct()
    {

        $data = $this->getTestData();

        $iterator = new ArrayIterator($data);

        $search = $this->newSearch($iterator, new GenericSearchTest_OrmObject());
        $this->assertEquals($data, iterator_to_array($search));
        $this->assertSame($iterator, $search->getResult());
    }

    public function test_pass_callable_resultProvider_in___construct()
    {

        $data = $this->getTestData();

        $provider = new LoggingCallable(function ($filters, $sorting, $queryKeys, $search) use ($data) {
            return $data;
        });

        $search = $this->newSearch($provider, new GenericSearchTest_OrmObject());

        $search->filter('foo', 'bar');
        $search->sort('foo', 'desc');

        $this->assertEquals($data, iterator_to_array($search));
        $this->assertEquals(iterator_to_array($search), $data);

        // Check the closure parameters
        $this->assertEquals(['foo' => 'bar'], $provider->arg(0)); // filter
        $this->assertEquals(['foo' => 'desc'], $provider->arg(1)); // sorting
        $this->assertEquals((new GenericSearchTest_OrmObject())->keys()->getSource(), $provider->arg(2)); // queryKeys
        $this->assertSame($search, $provider->arg(3));
    }

    public function test_empty_search()
    {
        $search = $this->newSearch();
        $this->assertEquals([], iterator_to_array($search));
    }

    public function test_automatic_pagination()
    {

        $data = $this->getTestData();

        $search = $this->newSearch($data, new GenericSearchTest_OrmObject());

        $paginator = $search->paginate(1, 10);
        $paginatorItems = iterator_to_array($paginator);

        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertCount(10, $paginator);

        $this->assertSame($data[0], $paginatorItems[0]);
        $this->assertSame($data[9], $paginatorItems[9]);

        $paginator2 = $search->paginate(2, 10);
        $paginatorItems2 = iterator_to_array($paginator2);

        $this->assertInstanceOf(Paginator::class, $paginator2);
        $this->assertCount(10, $paginator2);

        $this->assertSame($data[10], $paginatorItems2[0]);
        $this->assertSame($data[19], $paginatorItems2[9]);
    }

    public function test_pagination_by_custom_provider()
    {

        $data = $this->getTestData();

        $provider = new LoggingCallable(function ($page, $perPage, $filters, $sorting, $queryKeys, $search) use ($data) {
            $paginator = new \Ems\Pagination\Paginator($page+1, $perPage/2);
            $paginator->setResult($paginator->slice($data), count($data));
            return $paginator;
        });

        $search = $this->newSearch($data, new GenericSearchTest_OrmObject());
        $search->providePaginatorBy($provider);

        $search->filter('foo', 'bar');
        $search->sort('foo', 'desc');


        $paginator = $search->paginate(1, 10);
        $paginatorItems = iterator_to_array($paginator);

        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertCount(5, $paginator);

        $this->assertSame($data[5], $paginatorItems[0]);
        $this->assertSame($data[9], $paginatorItems[4]);

        $paginator2 = $search->paginate(2, 10);
        $paginatorItems2 = iterator_to_array($paginator2);

        $this->assertInstanceOf(Paginator::class, $paginator2);
        $this->assertCount(5, $paginator2);

        $this->assertSame($data[10], $paginatorItems2[0]);
        $this->assertSame($data[14], $paginatorItems2[4]);

        // Check the closure parameters
        $this->assertEquals(2, $provider->arg(0));
        $this->assertEquals(10, $provider->arg(1));
        $this->assertEquals(['foo' => 'bar'], $provider->arg(2)); // filter
        $this->assertEquals(['foo' => 'desc'], $provider->arg(3)); // sorting
        $this->assertEquals((new GenericSearchTest_OrmObject())->keys()->getSource(), $provider->arg(4)); // queryKeys
        $this->assertSame($search, $provider->arg(5));
    }


    public function _test_sort()
    {
        $search = $this->newSearch();
        $this->assertEquals([], $search->sorting());
        $this->assertSame($search, $search->sort('id'));
        $this->assertEquals(['id' => 'asc'], $search->sorting());
        $this->assertEquals('asc', $search->sorting('id'));
        $this->assertNull($search->sorting('updated_at'));
        $search->sort('updated_at', 'desc');
        $this->assertEquals(['id' => 'asc', 'updated_at' => 'desc'], $search->sorting());

        $this->assertTrue($search->isSortedBy('id'));
        $this->assertTrue($search->isSortedBy('updated_at'));
        $this->assertFalse($search->isSortedBy('created_at'));

        $this->assertEquals(1, $search->sortingPriority('id'));
        $this->assertEquals(2, $search->sortingPriority('updated_at'));
        $this->assertEquals(0, $search->sortingPriority('created_at'));

        $this->assertSame($search, $search->clearSort('id'));
        $this->assertEquals(['updated_at' => 'desc'], $search->sorting());
        $this->assertEquals(1, $search->sortingPriority('updated_at'));

        $search->clearSort();
        $this->assertEquals([], $search->sorting());
    }

    public function _test_apply_and_input()
    {
        $search = $this->newSearch();

        $input = [
            'foo' => 'bar',
            'baz' => [1,2],
            'no'  => true
        ];

        $this->assertSame($search, $search->apply($input));

        $this->assertEquals($input['foo'], $search->input('foo'));
        $this->assertEquals($input, $search->input());
        $this->assertNull($search->input('haha'));
        $this->assertEquals(true, $search->input('haha', true));
    }

    public function _test_filter()
    {
        $search = $this->newSearch();

        $search->filter('login', 'mathew')
               ->filter('active', true);

        $this->assertEquals('mathew', $search->filterValue('login'));
        $this->assertEquals(true, $search->filterValue('active'));

        $filters = [
            'age'      => 14,
            'q'        => 'anton',
            'location' => 'New York'
        ];

        $search->filter($filters);

        // Assure we didn't overwrite the previous filters
        $this->assertEquals('mathew', $search->filterValue('login'));
        $this->assertEquals(true, $search->filterValue('active'));

        $this->assertEquals(14, $search->filterValue('age'));
        $this->assertEquals('anton', $search->filterValue('q'));
        $this->assertEquals('New York', $search->filterValue('location'));
        $this->assertNull($search->filterValue('foo'));

    }

    public function _test_hasFilter_returns_true_if_filter_was_set()
    {
        $search = $this->newSearch();

        $filters = [
            'age'           => 14,
            'q'             => 'anton',
            'location'      => 'New York',
            'last_login'    => null
        ];

        $search->filter($filters);

        foreach (array_keys($filters) as $key) {
            $this->assertTrue($search->hasFilter($key));
        }

        $this->assertFalse($search->hasFilter('foo'));
    }

    public function _test_hasFilter_returns_true_if_filter_was_set_with_value()
    {
        $search = $this->newSearch();

        $filters = [
            'age'           => 14,
            'q'             => 'anton',
            'location'      => 'New York',
            'category'      => [4, 6],
            'last_login'    => null
        ];

        $search->filter($filters);

        foreach ($filters as $key=>$value) {
            $this->assertTrue($search->hasFilter($key, $value));
        }

        // Test possible "in" filter in category
        $this->assertTrue($search->hasFilter('category', 4));
        $this->assertTrue($search->hasFilter('category', 6));
        $this->assertFalse($search->hasFilter('category', 3));
    }

    public function _test_clearFilter_returns_true_if_filter_was_set()
    {
        $search = $this->newSearch();

        $filters = [
            'age'           => 14,
            'q'             => 'anton',
            'location'      => 'New York',
            'last_login'    => null
        ];

        $search->filter($filters);

        foreach (array_keys($filters) as $key) {
            $this->assertTrue($search->hasFilter($key));
        }

        $this->assertSame($search, $search->clearFilter('age'));
        $this->assertFalse($search->hasFilter('age'));
        $this->assertTrue($search->hasFilter('q'));

        $search->clearFilter();

        foreach (array_keys($filters) as $key) {
            $this->assertFalse($search->hasFilter($key));
        }

    }

    public function _test_filterKeys()
    {
        $search = $this->newSearch();

        $filters = [
            'age'           => 14,
            'q'             => 'anton',
            'location'      => 'New York',
            'category'      => [4, 6],
            'last_login'    => null
        ];

        $search->filter($filters);

        foreach ($filters as $key=>$value) {
            $this->assertTrue($search->filterKeys()->contains($key));
        }

        $this->assertEquals(array_keys($filters), $search->filterKeys()->getSource());
    }

    public function _test_fill_by_input()
    {
        $input = [
            'age'           => 14,
            'q'             => 'anton',
            'location'      => 'New York',
            'category'      => [4, 6],
            'last_login'    => null,
            '_sort'         => 'last_login'
        ];

        $search = $this->newSearch()->apply($input);

        foreach ($input as $key=>$value) {

            if ($key == '_sort') {
                continue;
            }

            $this->assertEquals($value, $search->filterValue($key));
            $this->assertTrue($search->hasFilter($key));
        }

        $this->assertEquals('asc', $search->sorting('last_login'));
        $this->assertEquals(1, $search->sortingPriority('last_login'));

    }

    public function _test_fill_by_input_with_direction()
    {
        $input = [
            'age'           => 14,
            'q'             => 'anton',
            'location'      => 'New York',
            'category'      => [4, 6],
            'last_login'    => null,
            '_sort'         => 'last_login:desc'
        ];

        $search = $this->newSearch()->apply($input);

        foreach ($input as $key=>$value) {

            if ($key == '_sort') {
                continue;
            }

            $this->assertEquals($value, $search->filterValue($key));
            $this->assertTrue($search->hasFilter($key));
        }

        $this->assertEquals('desc', $search->sorting('last_login'));
        $this->assertEquals(1, $search->sortingPriority('last_login'));

    }

    public function _test_fill_by_input_with_multiple_sort()
    {
        $input = [
            'age'           => 14,
            'q'             => 'anton',
            'location'      => 'New York',
            'category'      => [4, 6],
            'last_login'    => null,
            '_sort'         => ['last_login:desc', 'id:asc']
        ];

        $search = $this->newSearch()->apply($input);

        foreach ($input as $key=>$value) {

            if ($key == '_sort') {
                continue;
            }

            $this->assertEquals($value, $search->filterValue($key));
            $this->assertTrue($search->hasFilter($key));
        }

        $this->assertEquals('desc', $search->sorting('last_login'));
        $this->assertEquals(1, $search->sortingPriority('last_login'));
        $this->assertEquals('asc', $search->sorting('id'));
        $this->assertEquals(2, $search->sortingPriority('id'));

    }

    public function _test_keys_forwards_to_ormObject()
    {
        $engine = $this->mockEngine();
        $ormObject1 = new GenericSearchTest_OrmObject();

        $search = $this->newSearch($engine, $ormObject1, $this);


        $this->assertSame($ormObject1, $search->ormObject());
        $ormObject = new GenericSearchTest_OrmObject();
        $search->setOrmObject($ormObject);
        $this->assertSame($ormObject, $search->ormObject());

        $ormKeys = $ormObject->keys();

        $this->assertEquals($ormKeys->getSource(), $search->keys()->getSource());

    }

    public function _test_getIterator_forwards_to_engine()
    {
        $engine = $this->mockEngine();
        $ormObject1 = new GenericSearchTest_OrmObject();

        $search = $this->newSearch($engine, $ormObject1, $this);


        $this->assertSame($ormObject1, $search->ormObject());
        $ormObject = new GenericSearchTest_OrmObject();
        $search->setOrmObject($ormObject);
        $this->assertSame($ormObject, $search->ormObject());

        $ormKeys = $ormObject->keys();
        $query = new ConditionGroup();

        $results = [
            new GenericSearchTest_OrmObject(['id' => 1]),
            new GenericSearchTest_OrmObject(['id' => 2]),
            new GenericSearchTest_OrmObject(['id' => 3]),
            new GenericSearchTest_OrmObject(['id' => 4])
        ];

        $result = new GenericResult(function () use ($results) {
            return $results;
        }, $engine);

        $engine->shouldReceive('newQuery')
               ->andReturn($query)
               ->once();

        $engine->shouldReceive('search')
               ->with($query, [], $ormKeys->getSource())
               ->once()
               ->andReturn($result);

        $payload = iterator_to_array($search);

        foreach ($results as $key=>$value) {
            $this->assertEquals($value->getId(), $payload[$key]->getId());
        }

        $this->assertSame($this, $search->creator());

    }

    public function _test_buildConditions()
    {
        $engine = $this->mockEngine();
        $ormObject = new GenericSearchTest_OrmObject();

        $search = $this->newSearch($engine, $ormObject, $this);


        $ormKeys = $ormObject->keys();
        $query = new ConditionGroup();

        $results = [
            new GenericSearchTest_OrmObject(['id' => 1]),
            new GenericSearchTest_OrmObject(['id' => 2]),
            new GenericSearchTest_OrmObject(['id' => 3]),
            new GenericSearchTest_OrmObject(['id' => 4])
        ];

        $result = new GenericResult(function () use ($results) {
            return $results;
        }, $engine);

        $engine->shouldReceive('newQuery')
            ->andReturn($query)
            ->once(); // This assures that the conditions are built once

        $sort = [
            'last_login' => 'desc',
            'id' => 'asc'
        ];

        $engine->shouldReceive('search')
            ->with(Mockery::type(get_class($query)), $sort, $ormKeys->getSource())
            ->once() // due caching
            ->andReturn($result);

        $input = [
            'age' => 14,
            'q' => 'anton',
            'location' => 'New York',
            'category' => [4, 6],
            'last_login' => null,
            '_sort' => ['last_login:desc', 'id:asc']
        ];


        $search->apply($input);

        $payload = iterator_to_array($search);

        foreach ($results as $key => $value) {
            $this->assertEquals($value->getId(), $payload[$key]->getId());
        }

        // Second retrieval to force second load but one condition rendering
        $payload = iterator_to_array($search);

        foreach ($results as $key => $value) {
            $this->assertEquals($value->getId(), $payload[$key]->getId());
        }

        /** @var ConditionGroup $condition */
        $condition = Cheat::get($search, 'conditions');

        foreach ($search->filterKeys() as $key) {
            $this->assertTrue($condition->keys()->contains($key));
        }

    }

    public function _test_paginate_with_unpaginatable_result_throws_exception()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\Unsupported::class);
        $engine = $this->mockEngine();
        $ormObject = new GenericSearchTest_OrmObject();

        $search = $this->newSearch($engine, $ormObject, $this);


        $ormKeys = $ormObject->keys();
        $query = new ConditionGroup();

        $results = [
            new GenericSearchTest_OrmObject(['id' => 1]),
            new GenericSearchTest_OrmObject(['id' => 2]),
            new GenericSearchTest_OrmObject(['id' => 3]),
            new GenericSearchTest_OrmObject(['id' => 4])
        ];

        $result = new GenericResult(function () use ($results) {
            return $results;
        }, $engine);

        $engine->shouldReceive('newQuery')
            ->andReturn($query)
            ->once(); // This assures that the conditions are built once

        $sort = [
            'last_login' => 'desc',
            'id' => 'asc'
        ];

        $engine->shouldReceive('search')
            ->with(Mockery::type(get_class($query)), $sort, $ormKeys->getSource())
            ->once()
            ->andReturn($result);

        $input = [
            'age' => 14,
            'q' => 'anton',
            'location' => 'New York',
            'category' => [4, 6],
            'last_login' => null,
            '_sort' => ['last_login:desc', 'id:asc']
        ];


        $search->apply($input);

        $search->paginate();

    }

    public function _test_paginate()
    {
        $engine = $this->mockEngine();
        $ormObject = new GenericSearchTest_OrmObject();

        $search = $this->newSearch($engine, $ormObject, $this);


        $ormKeys = $ormObject->keys();
        $query = new ConditionGroup();

        $results = [
            new GenericSearchTest_OrmObject(['id' => 1]),
            new GenericSearchTest_OrmObject(['id' => 2]),
            new GenericSearchTest_OrmObject(['id' => 3]),
            new GenericSearchTest_OrmObject(['id' => 4]),
            new GenericSearchTest_OrmObject(['id' => 5]),
            new GenericSearchTest_OrmObject(['id' => 6]),
            new GenericSearchTest_OrmObject(['id' => 7]),
            new GenericSearchTest_OrmObject(['id' => 8]),
            new GenericSearchTest_OrmObject(['id' => 9]),
            new GenericSearchTest_OrmObject(['id' => 10])
        ];

        $result = new GenericPaginatableResult(function () use ($results) {
            return $results;
        },function ($page, $perPage) use ($results) {
            return array_slice($results, ($page-1)*$perPage, $perPage);
        }, $engine);

        $engine->shouldReceive('newQuery')
            ->andReturn($query)
            ->once(); // This assures that the conditions are built once

        $sort = [
            'last_login' => 'desc',
            'id' => 'asc'
        ];

        $engine->shouldReceive('search')
            ->with(Mockery::type(get_class($query)), $sort, $ormKeys->getSource())
            ->once() // This assures that the result is fetched once
            ->andReturn($result);

        $input = [
            'age' => 14,
            'q' => 'anton',
            'location' => 'New York',
            'category' => [4, 6],
            'last_login' => null,
            '_sort' => ['last_login:desc', 'id:asc']
        ];


        $search->apply($input);

        $payload = $search->paginate(1, 4);

        $this->assertCount(4, $payload);

        $payload = $search->paginate(1, 4);

        $this->assertCount(4, $payload);

    }

    protected function newSearch($result=null, OrmObjectContract $ormObject=null, $creator=null)
    {
        return new GenericSearch($ormObject ?: new GenericSearchTest_OrmObject(), $result, $creator);
    }

    protected function getTestData()
    {
        return [
            new GenericSearchTest_OrmObject(['id' => 1]),
            new GenericSearchTest_OrmObject(['id' => 2]),
            new GenericSearchTest_OrmObject(['id' => 3]),
            new GenericSearchTest_OrmObject(['id' => 4]),
            new GenericSearchTest_OrmObject(['id' => 5]),
            new GenericSearchTest_OrmObject(['id' => 6]),
            new GenericSearchTest_OrmObject(['id' => 7]),
            new GenericSearchTest_OrmObject(['id' => 8]),
            new GenericSearchTest_OrmObject(['id' => 9]),
            new GenericSearchTest_OrmObject(['id' => 10]),
            new GenericSearchTest_OrmObject(['id' => 11]),
            new GenericSearchTest_OrmObject(['id' => 12]),
            new GenericSearchTest_OrmObject(['id' => 13]),
            new GenericSearchTest_OrmObject(['id' => 14]),
            new GenericSearchTest_OrmObject(['id' => 15]),
            new GenericSearchTest_OrmObject(['id' => 16]),
            new GenericSearchTest_OrmObject(['id' => 17]),
            new GenericSearchTest_OrmObject(['id' => 18]),
            new GenericSearchTest_OrmObject(['id' => 19]),
            new GenericSearchTest_OrmObject(['id' => 20])
        ];
    }
}

class GenericSearchTest_OrmObject extends OrmObject
{
    public function keys()
    {
        return new StringList(['id', 'login', 'last_login', 'age', 'created_at', 'categories']);
    }
}