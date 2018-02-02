<?php
/**
 *  * Created by mtils on 27.01.18 at 15:54.
 **/

namespace Ems\Queue;

use DateTime;
use Ems\Contracts\Queue\Job;
use Ems\Contracts\Queue\Queue as QueueContract;
use Ems\Contracts\Queue\Driver;
use Ems\Queue\Drivers\NullDriver;
use Ems\TestCase;

class QueueTest extends TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(QueueContract::class, $this->newQueue());
    }

    public function test_run_creates_job()
    {
        $queue = $this->newQueue();

        $operation = [QueueTest::class, 'nullDriver'];
        $args = [1, 2];
        $handle = 'handle';

        $this->assertEquals(0, $queue->count());
        $job = $queue->run($operation, $args, $handle);

        $this->assertEquals(1, $queue->count());

        $this->assertInstanceOf(Job::class, $job);
        $this->assertEquals($operation, $job->operation());
        $this->assertEquals($args, $job->arguments());
        $this->assertEquals(QueueContract::QUEUED, $job->state());

        $this->assertEquals([Queue::$defaultChannel], $queue->channelNames());

        $this->assertEquals([$job], $queue->all());
    }

    public function test_at_sets_plannedStart()
    {
        $queue = $this->newQueue();

        $date = new DateTime('2017-12-01 00:00:00');
        $operation = [QueueTest::class, 'nullDriver'];
        $args = [1, 2];
        $handle = 'handle';

        $job = $queue->at($date)->run($operation, $args, $handle);

        $this->assertInstanceOf(Job::class, $job);
        $this->assertEquals($operation, $job->operation());
        $this->assertEquals($args, $job->arguments());
        $this->assertEquals(QueueContract::QUEUED, $job->state());

        $this->assertEquals($date, $job->plannedStart());
    }

    public function test_at_sets_plannedStart_by_planned_start()
    {
        $queue = $this->newQueue();

        $start = '+1 day';
        $operation = [QueueTest::class, 'nullDriver'];
        $args = [1, 2];
        $handle = 'handle';

        $job = $queue->at($start)->run($operation, $args, $handle);

        $this->assertInstanceOf(Job::class, $job);
        $this->assertEquals($operation, $job->operation());
        $this->assertEquals($args, $job->arguments());
        $this->assertEquals(QueueContract::QUEUED, $job->state());

        $this->assertGreaterThan(new DateTime(), $job->plannedStart());
    }

    public function test_tries_sets_maxTries()
    {
        $queue = $this->newQueue();

        $tries = 16;
        $operation = [QueueTest::class, 'nullDriver'];
        $args = [1, 2];
        $handle = 'handle';

        $job = $queue->tries($tries)->run($operation, $args, $handle);

        $this->assertInstanceOf(Job::class, $job);
        $this->assertEquals($operation, $job->operation());
        $this->assertEquals($args, $job->arguments());
        $this->assertEquals(QueueContract::QUEUED, $job->state());

        $this->assertEquals($tries, $job->maxTries());
    }

    public function test_timeout_sets_timeout()
    {
        $queue = $this->newQueue();

        $timeout = 86400;
        $operation = [QueueTest::class, 'nullDriver'];
        $args = [1, 2];
        $handle = 'handle';

        $job = $queue->timeout($timeout)->run($operation, $args, $handle);

        $this->assertInstanceOf(Job::class, $job);
        $this->assertEquals($operation, $job->operation());
        $this->assertEquals($args, $job->arguments());
        $this->assertEquals(QueueContract::QUEUED, $job->state());

        $this->assertEquals($timeout, $job->timeout());
    }

    public function test_channel_sets_channel()
    {
        $otherChannel = $this->mock(Driver::class);

        $queue = $this->newQueue();
        $this->assertSame($queue, $queue->addChannel('local', $otherChannel));

        $operation = [QueueTest::class, 'nullDriver'];
        $args = [1, 2];
        $handle = 'handle';

        $otherChannel->shouldReceive('push')
                     ->once()
                     ->andReturn(Queue::RUNNING);

        $job = $queue->onChannel('local')->run($operation, $args, $handle);

        $this->assertInstanceOf(Job::class, $job);
        $this->assertEquals($operation, $job->operation());
        $this->assertEquals($args, $job->arguments());
        $this->assertEquals(QueueContract::RUNNING, $job->state());

        $this->assertEquals('local', $job->channelName());

        $otherChannel->shouldReceive('pop')
                     ->with('local')
                     ->andReturn($job);

        $this->assertSame($job, $queue->pop('local'));
    }

    public function test_removeChannel_removes_channel()
    {
        $queue = $this->newQueue();
        $this->assertSame($queue, $queue->addChannel('remote', $this->nullDriver()));

        $this->assertEquals([Queue::$defaultChannel, 'remote'], $queue->channelNames());
        $this->assertSame($queue, $queue->removeChannel('remote'));
        $this->assertEquals([Queue::$defaultChannel], $queue->channelNames());
    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\NotFound
     */
    public function test_push_to_unknown_channel_throws_exception()
    {
        $queue = $this->newQueue();
        $queue->onChannel('foo')->run('strpos');
    }

    protected function newQueue(Driver $driver=null)
    {
        return new Queue($driver ?: $this->nullDriver());
    }

    protected function nullDriver()
    {
        return new NullDriver();
    }
}