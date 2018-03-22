<?php
/**
 *  * Created by mtils on 04.02.18 at 07:21.
 **/

namespace Ems\Queue\Illuminate;


use Ems\Contracts\Core\IOCContainer;
use Ems\Contracts\Core\Type;
use Ems\Core\Lambda;
use Ems\Queue\ArgumentSerializer;
use function func_get_args;
use Illuminate\Contracts\Queue\ShouldQueue;
use ReflectionException;
use UnderflowException;

class ProxyJob implements ShouldQueue
{
    /**
     * @var IOCContainer
     */
    protected $ioc;

    /**
     * @var ArgumentSerializer
     */
    protected $serializer;

    /**
     * ProxyJob constructor.
     *
     * @param IOCContainer       $ioc
     * @param ArgumentSerializer $serializer
     */
    public function __construct(IOCContainer $ioc, ArgumentSerializer $serializer)
    {
        $this->ioc = $ioc;
        $this->serializer = $serializer;
    }

    /**
     * Perform the serialized operation.
     *
     * @param object $nativeJob
     * @param array $data
     *
     * @return mixed
     *
     * @throws ReflectionException
     * @throws UnderflowException
     */
    public function fire($nativeJob, array $data)
    {
        if (!isset($data['operation'])) {
            throw new UnderflowException("Operation not set in data.");
        }

        $arguments = isset($data['arguments']) ? $this->decodeArguments($data['arguments']) : [];
        $lambda = new Lambda($data['operation'], $this->ioc);

        return $this->ioc->call($lambda->getCallable(), $arguments);

    }

    /**
     * @param array $arguments
     *
     * @return array
     */
    protected function decodeArguments(array $arguments)
    {
        return $this->serializer->decode($arguments);
    }

}