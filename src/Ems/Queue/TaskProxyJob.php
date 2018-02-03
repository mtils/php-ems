<?php
/**
 *  * Created by mtils on 28.01.18 at 07:40.
 **/

namespace Ems\Queue;


use Ems\Contracts\Core\Chatty;
use Ems\Contracts\Core\IOCContainer;
use Ems\Contracts\Core\Progress;
use Ems\Contracts\Core\PublishesProgress;
use Ems\Contracts\Queue\TaskRepository;
use Ems\Core\Lambda;

class TaskProxyJob
{
    /**
     * @var IOCContainer
     */
    protected $ioc;

    /**
     * @var TaskRepository
     */
    protected $taskRepository;

    /**
     * TaskProxyJob constructor.
     *
     * @param IOCContainer   $ioc
     * @param TaskRepository $taskRepository
     */
    public function __construct(IOCContainer $ioc, TaskRepository $taskRepository)
    {
        $this->ioc = $ioc;
        $this->taskRepository = $taskRepository;
    }

    /**
     * Run the desired task and log its state changes.
     *
     * @param int|string $taskId
     * @param string     $operation
     * @param array      $arguments
     *
     * @return mixed
     */
    public function run($taskId, $operation, array $arguments)
    {

        try {

            $lambda = new Lambda($operation, $this->ioc);

            if ($lambda->isInstanceMethod()) {
                $this->connectHooks($taskId, $lambda->getCallInstance());
            }

            $this->taskRepository->write($taskId, ['state' => Queue::RUNNING]);

            $result = $this->performTask($lambda, $arguments);

            $this->taskRepository->write($taskId, ['state' => Queue::FINISHED]);

            return $result;

        } catch (\Exception $e) {

            $this->taskRepository->writeMessage($taskId, $this->exceptionMessage($e), Chatty::FATAL);
            $this->taskRepository->write($taskId, ['state' => Queue::FAILED]);

        }

    }

    /**
     * Perform th task (run the job).
     *
     * @param Lambda $lambda
     * @param array  $arguments
     *
     * @return mixed
     *
     * @throws \ReflectionException
     */
    protected function performTask(Lambda $lambda, array $arguments)
    {
        return $this->ioc->call($lambda->getCallable(), $arguments);
    }

    /**
     * Log messages and progress if this is emitted by the operation.
     *
     * @param int|string    $taskId
     * @param object        $jobObject
     */
    protected function connectHooks($taskId, $jobObject)
    {

        if ($jobObject instanceof Chatty) {
            $jobObject->onMessage(function ($message, $level=Chatty::INFO) use ($taskId) {
                $this->writeMessage($taskId, $message, $level);
            });
        }

        if ($jobObject instanceof PublishesProgress) {
            $jobObject->onProgressChanged(function (Progress $progress) use ($taskId) {
                $this->writeProgress($taskId, $progress);
            });
        }
    }

    /**
     * @param string|int $taskId
     * @param string     $message
     * @param string     $level
     */
    protected function writeMessage($taskId, $message, $level)
    {
        $this->taskRepository->writeMessage($taskId, $message, $level);
    }

    /**
     * @param int|string $taskId
     * @param Progress   $progress
     */
    protected function writeProgress($taskId, Progress $progress)
    {
        $this->taskRepository->writeProgress($taskId, $progress);
    }

    /**
     * Format an exception for logging it.
     *
     * @param \Exception $e
     *
     * @return string
     */
    protected function exceptionMessage(\Exception $e)
    {
        return get_class($e) . ' in ' . $e->getFile() . ':' . $e->getLine() . ' "' . $e->getMessage() . '"';
    }
}