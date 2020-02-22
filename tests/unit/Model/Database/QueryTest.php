<?php

namespace Ems\Model\Database;

use Ems\TestCase;
use function str_replace;

/**
 *  * Created by mtils on 22.02.20 at 10:40.
 **/

class QueryTest extends TestCase
{
    protected function assertSql($expected, $actual, $message='')
    {
        $expectedCmp = str_replace("\n", ' ', $expected);
        $actualCmp = str_replace("\n", ' ', $actual);
        $message = $message ?: "Expected SQL: '$expected' did not match '$actual";
        $this->assertEquals($expectedCmp, $actualCmp, $message);
    }
}