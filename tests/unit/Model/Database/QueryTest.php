<?php

namespace Ems\Model\Database;

use Ems\Contracts\Core\Renderable;
use Ems\Contracts\Model\PaginatableResult;
use Ems\TestCase;
use function str_replace;
use Ems\Contracts\Model\Database\Query as BaseQuery;

/**
 *  * Created by mtils on 22.02.20 at 10:40.
 **/

class QueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interfaces()
    {
        $this->assertInstanceOf(BaseQuery::class, $this->newQuery());
        $this->assertInstanceOf(Renderable::class, $this->newQuery());
        $this->assertInstanceOf(PaginatableResult::class, $this->newQuery());
    }

    /**
     * @test
     */
    public function mimeType_is_sql()
    {
        $this->assertEquals('application/sql', $this->newQuery()->mimeType());
    }

    protected function newQuery()
    {
        return new Query();
    }

    protected function assertSql($expected, $actual, $message='')
    {
        $expectedCmp = str_replace("\n", ' ', $expected);
        $actualCmp = str_replace("\n", ' ', $actual);
        $message = $message ?: "Expected SQL: '$expected' did not match '$actual";
        $this->assertEquals($expectedCmp, $actualCmp, $message);
    }
}