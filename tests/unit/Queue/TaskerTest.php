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
use RuntimeException;

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

    public function test_pop_job_from_queue()
    {
        $queue = $this->newQueue();
        $tasker = $this->newTasker($queue);

        $operation = [TaskerTest::class, 'fake'];
        $args = [1, 2];

        $task = $tasker->run($operation, $args);
        $this->assertInstanceOf(Task::class, $task);

        $job = $queue->pop();

        $this->assertEquals(Queue::QUEUED, $job->state());
        $this->assertEquals(TaskProxyJob::class, $job->operation()[0]);
        $this->assertEquals('run', $job->operation()[1]);
        $this->assertEquals($task->getId(), $job->arguments()[0]);
        $this->assertEquals(TaskerTest::class . '->' . 'fake', $job->arguments()[1]);
        $this->assertEquals($args, $job->arguments()[2]);

        $this->assertNull($queue->pop());
        $this->assertNull($this->nullDriver()->pop('foo'));
        $this->assertEmpty($this->nullDriver()->all('foo'));
    }

    public function test_NullTaskRepository_get()
    {
        $taskRepo = $this->nullRepo();
        $task = $taskRepo->create([]);
        $this->assertInstanceOf(Task::class, $task);
        $this->assertSame($task, $taskRepo->get($task->getId()));
        $this->assertSame($task, $taskRepo->getOrFail($task->getId()));
        $this->assertEquals('foo', $taskRepo->get(11546878974, 'foo'));
    }

    public function test_NullTaskRepository_getOrFail_throws_exception()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\NotFound::class);
        $taskRepo = $this->nullRepo();
        $taskRepo->getOrFail('hihihaha');
    }

    public function test_NullTaskRepository_delete()
    {
        $taskRepo = $this->nullRepo();
        $task = $taskRepo->create([]);
        $this->assertInstanceOf(Task::class, $task);

        $this->assertSame($task, $taskRepo->get($task->getId()));

        $this->assertTrue($taskRepo->delete($task->getId()));

        $this->assertNull($taskRepo->get($task->getId()));

        $this->assertFalse($taskRepo->delete($task->getId()));
    }

    public function test_NullTaskRepository_purge()
    {
        $taskRepo = $this->nullRepo();
        $task = $taskRepo->create([]);
        $this->assertInstanceOf(Task::class, $task);

        $this->assertSame($task, $taskRepo->get($task->getId()));

        $this->assertEquals(0, $taskRepo->purge());

        $task->state = Queue::FINISHED;

        $this->assertEquals(1, $taskRepo->purge());

    }

    public function test_NullTaskRepository_sync()
    {
        $taskRepo = $this->nullRepo();
        $queue = $this->newQueue();
        $tasker = $this->newTasker($queue, $taskRepo);

        $operation = [TaskerTest::class, 'fake'];
        $args = [1, 2];

        $task = $tasker->run($operation, $args);
        $this->assertInstanceOf(Task::class, $task);

        $job = $task->getJob();
        $jobWithoutTask = $queue->run('str_replace');

        $this->assertEquals(1, $taskRepo->sync([$job, $jobWithoutTask]));

    }

    public function test_NullTaskRepository_failing_transaction()
    {
        $this->expectException(\RuntimeException::class);
        $taskRepo = $this->nullRepo();

        $taskRepo->transaction(function($repo){
            $this->assertTrue($repo->isInTransaction());
            $task = $repo->create([]);
            throw new RuntimeException();

        });

        $this->assertFalse($taskRepo->isInTransaction());

    }

    public function test_NullTaskRepository_failing_transaction_without_task_creation()
    {
        $this->expectException(\RuntimeException::class);
        $taskRepo = $this->nullRepo();

        $taskRepo->transaction(function($repo){
            $this->assertTrue($repo->isInTransaction());
            throw new RuntimeException();

        });

        $this->assertFalse($taskRepo->isInTransaction());

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