<?php
/**
 *  * Created by mtils on 27.01.18 at 15:54.
 **/

namespace Ems\Queue;

use DateTime;
use Ems\Contracts\Queue\Job;
use Ems\Contracts\Queue\Task;
use Ems\Contracts\Queue\Tasker as TaskerContract;
use Ems\Contracts\Queue\Queue as QueueContract;
use Ems\Contracts\Queue\Driver;
use Ems\Contracts\Queue\TaskRepository;
use Ems\Queue\Drivers\NullDriver;
use Ems\Queue\Task\NullTaskRepository;
use Ems\TestCase;

class TaskerTest extends TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(TaskerContract::class, $this->newTasker());
    }

    public function test_run_creates_task()
    {

        $tasker = $this->newTasker();

        $operation = [TaskerTest::class, 'fake'];
        $args = [1, 2];

        $task = $tasker->run($operation, $args);
        $this->assertInstanceOf(Task::class, $task);

        $job = $task->getJob();

        $this->assertGreaterThan(0, $task->getId());

        $this->assertEquals(Queue::QUEUED, $job->state());
        $this->assertEquals(Queue::QUEUED, $task->getState());
        $this->assertEquals(TaskProxyJob::class, $job->operation()[0]);
        $this->assertEquals('run', $job->operation()[1]);
        $this->assertEquals($task->getId(), $job->arguments()[0]);
        $this->assertEquals(TaskerTest::class . '->' . 'fake', $job->arguments()[1]);
        $this->assertEquals($args, $job->arguments()[2]);

    }

    public function test_run_creates_task_with_job_attributes()
    {

        $queue = $this->newQueue();
        $queue->addChannel('remote', $this->nullDriver());

        $tasker = $this->newTasker($queue);

        $operation = [TaskerTest::class, 'fake'];
        $args = [1, 2];
        $plannedStart = (new DateTime())->modify('+1 day');
        $tries = 17;
        $timeout = 86400;
        $channel = 'remote';

        $task = $tasker->at($plannedStart)
            ->tries($tries)
            ->timeout($timeout)
            ->onChannel($channel)
            ->run($operation, $args);
        $this->assertInstanceOf(Task::class, $task);

        $job = $task->getJob();

        $this->assertGreaterThan(0, $task->getId());

        $this->assertEquals(Queue::QUEUED, $job->state());
        $this->assertEquals(Queue::QUEUED, $task->getState());
        $this->assertEquals(TaskProxyJob::class, $job->operation()[0]);
        $this->assertEquals('run', $job->operation()[1]);
        $this->assertEquals($task->getId(), $job->arguments()[0]);
        $this->assertEquals(TaskerTest::class . '->' . 'fake', $job->arguments()[1]);
        $this->assertEquals($args, $job->arguments()[2]);

        $this->assertEquals($plannedStart, $job->plannedStart());
        $this->assertEquals($tries, $job->maxTries());
        $this->assertEquals($timeout, $job->timeout());
        $this->assertEquals($channel, $job->channelName());

    }

    public function test_run_creates_task_with_job_and_task_attributes()
    {

        $queue = $this->newQueue();
        $queue->addChannel('remote', $this->nullDriver());

        $tasker = $this->newTasker($queue);

        $operation = [TaskerTest::class, 'fake'];
        $args = [1, 2];
        $plannedStart = (new DateTime())->modify('+1 day');
        $tries = 17;
        $timeout = 86400;
        $channel = 'remote';

        $class = static::class;
        $id = 53;
        $user = 'tom';
        $name = 'user-import';

        $task = $tasker->at($plannedStart)
            ->tries($tries)
            ->timeout($timeout)
            ->onChannel($channel)
            ->forEntity($class, $id)
            ->byUser($user)
            ->named($name)
            ->run($operation, $args);

        $this->assertInstanceOf(Task::class, $task);

        $job = $task->getJob();

        $this->assertGreaterThan(0, $task->getId());

        $this->assertEquals(Queue::QUEUED, $job->state());
        $this->assertEquals(Queue::QUEUED, $task->getState());
        $this->assertEquals(TaskProxyJob::class, $job->operation()[0]);
        $this->assertEquals('run', $job->operation()[1]);
        $this->assertEquals($task->getId(), $job->arguments()[0]);
        $this->assertEquals(TaskerTest::class . '->' . 'fake', $job->arguments()[1]);
        $this->assertEquals($args, $job->arguments()[2]);

        $this->assertEquals($plannedStart, $job->plannedStart());
        $this->assertEquals($tries, $job->maxTries());
        $this->assertEquals($timeout, $job->timeout());
        $this->assertEquals($channel, $job->channelName());

        $this->assertEquals($class, $task->getAssociatedClass());
        $this->assertEquals($id, $task->getAssociatedId());
        $this->assertEquals($user, $task->getCreatorId());
        $this->assertEquals($name, $task->getName());

    }

    public function test_getQueue_returns_queue()
    {
        $queue = $this->newQueue();
        $this->assertSame($queue, $this->newTasker($queue)->getQueue());
    }

    public function test_methodHooks()
    {
        $this->assertEquals(['run'], $this->newTasker()->methodHooks());
    }

    protected function newTasker(QueueContract $queue=null, TaskRepository $repo=null)
    {
        $queue = $queue ?: $this->newQueue();
        $repo = $repo ?: $this->nullRepo();
        return new Tasker($queue, $repo);
    }

    protected function newQueue(Driver $driver=null)
    {
        return new Queue($driver ?: $this->nullDriver());
    }

    protected function nullDriver()
    {
        return new NullDriver();
    }

    protected function nullRepo()
    {
        return new NullTaskRepository();
    }

    public function fake()
    {
        return 'Hello';
    }
}