<?php
/**
 *  * Created by mtils on 25.08.19 at 15:24.
 **/

namespace unit\Core\Support;


use Ems\Contracts\Core\Chatty;
use Ems\Contracts\Core\Stream;
use Ems\Core\Filesystem\StringStream;
use Ems\Core\Support\ChattySupport;
use Ems\Skeleton\StreamLogger;
use Ems\Core\Url;
use Ems\TestCase;
use Psr\Log\LoggerInterface;
use stdClass;
use function strtolower;

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

    /**
     * @test
     */
    public function it_forwards_write_to_stream()
    {
        $stream = $this->mock(Stream::class);
        $output = 'foo';
        $stream->shouldReceive('write')->with($output)->andReturn('bar');
        $logger = $this->make($stream);
        $this->assertEquals('bar', $logger->write($output));
    }

    /**
     * @test
     */
    public function it_forwards_url_to_stream()
    {
        $stream = $this->mock(Stream::class);
        $url = new Url('https://foo.org');
        $stream->shouldReceive('url')->andReturn($url);
        $logger = $this->make($stream);
        $this->assertSame($url, $logger->url());
    }

    /**
     * @test
     */
    public function it_forwards_isOpen_to_stream()
    {
        $stream = $this->mock(Stream::class);
        $url = new Url('https://foo.org');
        $stream->shouldReceive('isOpen')->andReturn(true);
        $logger = $this->make($stream);
        $this->assertTrue($logger->isOpen());
    }

    /**
     * @test
     */
    public function it_forwards_open_to_stream()
    {
        $stream = $this->mock(Stream::class);
        $stream->shouldReceive('open')->once()->andReturn($stream);
        $logger = $this->make($stream);
        $this->assertSame($logger, $logger->open());
    }

    /**
     * @test
     */
    public function it_forwards_close_to_stream()
    {
        $stream = $this->mock(Stream::class);
        $stream->shouldReceive('close')->once()->andReturn($stream);
        $logger = $this->make($stream);
        $this->assertSame($logger, $logger->close());
    }

    /**
     * @test
     */
    public function it_forwards_resource_to_stream()
    {
        $resource = new stdClass();
        $stream = $this->mock(Stream::class);
        $stream->shouldReceive('resource')->andReturn($resource);
        $logger = $this->make($stream);
        $this->assertSame($resource, $logger->resource());
    }

    /**
     * @test
     */
    public function it_forwards_chatty_to_log()
    {
        $string = '';
        $stream = new StringStream($string);
        $logger = $this->make($stream);

        $chatty = new StreamLoggerTest_Chatty();
        $logger->forward($chatty);

        $chatty->trigger('Hello', Chatty::INFO);

        $this->assertContains('hello', strtolower($stream));

    }

    protected function make($target='php://temp')
    {
        return new \Ems\Skeleton\StreamLogger($target);
    }
}

class StreamLoggerTest_Chatty implements Chatty
{
    use ChattySupport;

    public function trigger($message, $level=Chatty::INFO)
    {
        $this->emitMessage($message, $level);
    }
}