<?php
/**
 *  * Created by mtils on 26.05.18 at 08:15.
 **/

namespace Ems\XType;


use Ems\IntegrationTest;
use Ems\Contracts\XType\Formatter as FormatterContract;


class FormatterIntegrationTest extends IntegrationTest
{
    public function test_resolving()
    {
        $this->assertInstanceOf(FormatterContract::class, $this->formatter());
    }

    protected function formatter()
    {
        return $this->app(FormatterContract::class);
    }
}