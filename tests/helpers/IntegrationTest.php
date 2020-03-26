<?php

namespace Ems;


use function realpath;

/**
 * Use this test if you need a running app
 **/
class IntegrationTest extends TestCase
{
    use AppTrait;
    use TestData;

    /**
     * @notest
     *
     * @param string $dir
     * @return string
     */
    protected static function dirOfTests($dir='')
    {
        return rtrim(realpath(__DIR__."/../../tests/" . $dir),'/');
    }
}
