<?php
/**
 *  * Created by mtils on 21.07.19 at 14:24.
 **/

namespace Ems\Routing;

use function call_user_func;
use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\InputHandler as InputHandlerContract;
use Ems\Contracts\Core\Response;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Core\Support\CustomFactorySupport;
use Ems\Contracts\Routing\MiddlewareCollection as MiddlewareCollectionContract;
use Exception;
use Throwable;

class InputHandler implements InputHandlerContract, SupportsCustomFactory
{
    use CustomFactorySupport;
    /**
     * @var callable
     */
    protected $exceptionHandler;

    /**
     * @var MiddlewareCollectionContract
     */
    protected $middleware;

    public function __construct(MiddlewareCollectionContract $middleware)
    {
        $this->middleware = $middleware;
        $this->exceptionHandler = function ($exception) {
            throw $exception;
        };
    }

    /**
     * Handle the input and return a corresponding
     *
     * @param Input $input
     *
     * @return Response
     */
    public function __invoke(Input $input)
    {
        try {
            return $this->middleware->__invoke($input);
        } catch (Exception $e) {
            call_user_func($this->exceptionHandler, $e);
        } catch (Throwable $e) {
            call_user_func($this->exceptionHandler, $e);
        }
    }

    /**
     * @return callable
     */
    public function getExceptionHandler()
    {
        return $this->exceptionHandler;
    }

    /**
     * Set an exception handler. It will receive any exception.
     *
     * @param callable $handler
     *
     * @return $this
     */
    public function setExceptionHandler(callable $handler)
    {
        $this->exceptionHandler = $handler;
        return $this;
    }


}