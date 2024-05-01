<?php

/**
 *  * Created by mtils on 23.02.20 at 11:25.
 **/

namespace Ems;

use InterNations\Component\HttpMock\PHPUnit\HttpMockFacade;
use InterNations\Component\HttpMock\PHPUnit\HttpMockTrait;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;

use PHPUnit\Framework\Attributes\Before;

use PHPUnit\Framework\Attributes\BeforeClass;

use function explode;
use function strlen;
use function substr;

abstract class HttpMockTest extends IntegrationTest
{
    //use HttpMockTrait;

    /**
     * @var string
     */
    protected static $host = 'localhost';

    /**
     * @var int
     */
    protected static $port = 8082;

    #[BeforeClass] public static function bootHttpMock()
    {
        static::setUpHttpMockBeforeClass(static::$port, static::$host);
    }

    #[AfterClass] public static function shutdownHttpMock()
    {
        static::tearDownHttpMockAfterClass();
    }

    #[Before] public function setUpHttp()
    {
        $this->setUpHttpMock();
    }

    #[After] public function tearDownHttp()
    {
        $this->tearDownHttpMock();
    }

    /**
     * Overwritten to make it work in php 7,4 with built in php server log output.
     */
    protected function tearDownHttpMock()
    {
        if (!$this->http) {
            return;
        }

        $http = $this->http;
        $this->http = null;
        $http->each(
            function (HttpMockFacade $facade) {
                static::assertSuccessFullPhpServerOutput((string) $facade->server->getIncrementalErrorOutput());
            }
        );
    }

    /**
     * Check (guess) if there are errors in the (console) output of php built
     * in web server
     *
     * @param string $output
     * @param string $message (optional)
     */
    public static function assertSuccessFullPhpServerOutput($output, $message = '')
    {
        foreach (explode("\n", $output) as $line) {
            if (static::isPhpServerErrorOutputLine($line)) {
                static::fail($message ?: "PHP Web Server logged an error: $line");
            }
        }
    }

    /**
     * @param string $line
     *
     * @return bool
     */
    public static function isPhpServerErrorOutputLine($line)
    {
        if (!$line) {
            return false;
        }

        $endsWith = function ($haystack, $needle) {
            return substr($haystack, -strlen($needle)) == $needle;
        };

        return !$endsWith($line, 'Accepted') && !$endsWith($line, 'Closing') && !$endsWith($line, ' started');
    }
}
