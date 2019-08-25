<?php
/**
 *  * Created by mtils on 25.08.19 at 15:24.
 **/

namespace unit\Core\Support;


use Ems\Core\Filesystem\StringStream;
use Ems\Core\Support\StreamLogger;
use Ems\TestCase;
use Psr\Log\LoggerInterface;

class StreamLoggerTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(LoggerInterface::class, $this->make());
    }

    /**
     * @test
     */
    public function get_and_set_target()
    {
        $logger = $this->make();
        $string = '';
        $stream = new StringStream($string);
        $this->assertSame($logger, $logger->setTarget($stream));
        $this->assertSame($stream, $logger->getTarget());
    }

    /**
     * @test
     */
    public function emergency_does_log()
    {
        $string = '';
        $stream = new StringStream($string);
        $logger = $this->make($stream);
        $logger->emergency('Boom');
        $this->assertContains('Boom', "$stream");
    }

    /**
     * @test
     */
    public function alert_does_log()
    {
        $string = '';
        $stream = new StringStream($string);
        $logger = $this->make($stream);
        $logger->alert('Boom');
        $this->assertContains('Boom', "$stream");
    }

    /**
     * @test
     */
    public function critical_does_log()
    {
        $string = '';
        $stream = new StringStream($string);
        $logger = $this->make($stream);
        $logger->critical('Boom');
        $this->assertContains('Boom', "$stream");
    }

    /**
     * @test
     */
    public function error_does_log()
    {
        $string = '';
        $stream = new StringStream($string);
        $logger = $this->make($stream);
        $logger->error('Boom');
        $this->assertContains('Boom', "$stream");
    }

    /**
     * @test
     */
    public function warning_does_log()
    {
        $string = '';
        $stream = new StringStream($string);
        $logger = $this->make($stream);
        $logger->warning('Boom');
        $this->assertContains('Boom', "$stream");
    }

    /**
     * @test
     */
    public function notice_does_log()
    {
        $string = '';
        $stream = new StringStream($string);
        $logger = $this->make($stream);
        $logger->notice('Boom');
        $this->assertContains('Boom', "$stream");
    }

    /**
     * @test
     */
    public function info_does_log()
    {
        $string = '';
        $stream = new StringStream($string);
        $logger = $this->make($stream);
        $logger->info('Boom');
        $this->assertContains('Boom', "$stream");
    }

    /**
     * @test
     */
    public function debug_does_log()
    {
        $string = '';
        $stream = new StringStream($string);
        $logger = $this->make($stream);
        $logger->debug('Boom');
        $this->assertContains('Boom', "$stream");
    }

    /**
     * @test
     */
    public function it_does_log_context()
    {
        $string = '';
        $stream = new StringStream($string);
        $logger = $this->make($stream);
        $logger->debug('Boom', ['foo' => 'bar']);
        $this->assertContains('Boom', "$stream");
        $this->assertContains('bar', "$stream");

    }

    protected function make($target='php://temp')
    {
        return new StreamLogger($target);
    }
}