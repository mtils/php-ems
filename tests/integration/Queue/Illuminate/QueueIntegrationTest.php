<?php
/**
 *  * Created by mtils on 05.02.18 at 05:48.
 **/

namespace Ems\Queue\Illuminate;

use Ems\Core\Application;
use Ems\Contracts\Queue\Queue;
use Ems\LaravelIntegrationTest;
use Illuminate\Queue\QueueServiceProvider;
use function func_get_args;

class QueueIntegrationTest extends LaravelIntegrationTest
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(Queue::class, $this->newQueue());
    }

    public function test_push_and_pull_job()
    {
        $queue = $this->newQueue();

        $operation = [QueueIntegrationTest_Job::class, 'run'];
        $args = ['Hello', 'p'];

        $job = $queue->run($operation, $args);

        $this->assertEquals($operation, $job->operation());
        $this->assertEquals($args, $job->arguments());

        $this->assertEquals($args, QueueIntegrationTest_Job::$receivedArgs);


    }

    /**
     * @return Queue
     */
    protected function newQueue()
    {
        return $this->app(Queue::class);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    protected function serviceProviders()
    {
        $baseProviders = parent::serviceProviders();
        $baseProviders[] = QueueServiceProvider::class;
        $baseProviders[] = EmsQueueServiceProvider::class;

        return $baseProviders;
    }

    protected function bootApplication(Application $app)
    {
        parent::bootApplication($app);
        $app->getContainer()->laravel()->instance('config', [
            'queue.default' => 'sync',
            'queue.connections.sync' => [
                'driver' => 'sync'
            ]
        ], true);
    }
}

class QueueIntegrationTest_Job
{
    public static $receivedArgs = [];

    public function run()
    {
        static::$receivedArgs = func_get_args();
    }
}