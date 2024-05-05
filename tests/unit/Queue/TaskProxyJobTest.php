<?php
/**
 *  * Created by mtils on 02.02.18 at 12:37.
 **/

namespace Ems\Queue;


use function array_reverse;
use Ems\Contracts\Core\Chatty;
use Ems\Contracts\Core\IOCContainer as IOCContainerContract;
use Ems\Contracts\Core\Progress;
use Ems\Contracts\Core\PublishesProgress;
use Ems\Contracts\Queue\TaskRepository;
use Ems\Core\IOCContainer;
use Ems\Core\Support\ChattySupport;
use Ems\Core\Support\ProgressSupport;
use Ems\Queue\Task\NullTaskRepository;
use Ems\TestCase;
use function func_get_args;
use LogicException;
use RuntimeException;

class TaskProxyJobTest extends TestCase
{

    public function test_instantiates()
    {
        $this->assertInstanceOf(TaskProxyJob::class, $this->newJob());
    }

    public function test_run_function()
    {
        $job = $this->newJob();

        $result = $job->run(42, 'str_replace', ['|', ' ', 'Hello|world']);

        $this->assertEquals('Hello world', $result);
    }

    public function test_run_static_method()
    {
        $job = $this->newJob();

        $result = $job->run(42, TaskProxyJobTest_Job::class . '::test', [1, 2, 3]);

        $this->assertEquals([1,2,3], $result);
    }

    public function test_run_instance_method()
    {
        $job = $this->newJob();

        $result = $job->run(42, TaskProxyJobTest_Job::class . '::run', [1, 2, 3]);

        $this->assertEquals([3,2,1], $result);
    }

    public function test_run_instance_method_of_object_with_hooks()
    {

        $repo = $this->nullTaskRepo();
        $task = $repo->create([]);

        $job = $this->newJob(null, $repo);

        $result = $job->run($task->getId(), TaskProxyJobTest_Emitter::class . '::run', [1, 2, 3]);


        $this->assertEquals('foo', $result);
        $this->assertEquals('Half done', $task->getMessage());
        $this->assertEquals(50, $task->getProgress()->percent);
        $this->assertEquals(Queue::FINISHED, $task->getState());

    }

    public function test_run_instance_method_of_object_that_throws_exception()
    {

        $repo = $this->nullTaskRepo();
        $task = $repo->create([]);

        $job = $this->newJob(null, $repo);

        $result = $job->run($task->getId(), TaskProxyJobTest_Emitter::class . '::fail', [1, 2, 3]);


        $this->assertNull($result);
        $this->assertStringContainsString(LogicException::class, $task->getMessage());
        $this->assertStringContainsString('makes no sense', $task->getMessage());
        $this->assertEquals(Queue::FAILED, $task->getState());

    }

    protected function newJob(IOCContainerContract $ioc=null, TaskRepository $taskRepo=null)
    {
        $ioc = $ioc ?: $this->newContainer();
        $taskRepo = $taskRepo ?: $this->nullTaskRepo();
        return new TaskProxyJob($ioc, $taskRepo);
    }

    protected function newContainer()
    {
        return new IOCContainer();
    }

    protected function nullTaskRepo()
    {
        return new NullTaskRepository();
    }
}

class TaskProxyJobTest_Job_Dependency
{
    public function transform($args)
    {
        return array_reverse($args);
    }
}

class TaskProxyJobTest_Job
{
    /**
     * @var TaskProxyJobTest_Job_Dependency
     */
    public $dependency;

    public function __construct(TaskProxyJobTest_Job_Dependency $dependency)
    {
        $this->dependency = $dependency;
    }

    public function run()
    {
        return $this->dependency->transform(func_get_args());
    }

    public static function test()
    {
        return func_get_args();
    }
}

class TaskProxyJobTest_Emitter implements PublishesProgress, Chatty
{
    use ProgressSupport;
    use ChattySupport;

    public function run()
    {
        $this->emitProgress(50);
        $this->emitMessage('Half done');
        return 'foo';
    }

    public function fail()
    {
        throw new LogicException('This makes no sense.');
    }
}