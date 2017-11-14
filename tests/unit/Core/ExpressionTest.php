<?php

namespace Ems\Core;

use Ems\Contracts\Core\Expression as ExpressionContract;

class ExpressionTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            ExpressionContract::class,
            $this->exp()
        );
    }

    public function test_it_returns_settet_string_in___toString()
    {
        $exp = $this->exp('SUM(amount) as balance');
        $this->assertEquals('SUM(amount) as balance', "$exp");
    }

    public function exp($raw='')
    {
        return new Expression($raw);
    }
}
