<?php
/**
 *  * Created by mtils on 21.07.19 at 14:24.
 **/

namespace Ems\Routing;

use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\InputHandler as InputHandlerContract;
use Ems\Contracts\Routing\MiddlewareCollection as MiddlewareCollectionContract;
use Ems\Core\ImmutableMessage;
use Ems\Core\Response;
use Ems\Core\Support\CustomFactorySupport;
use Exception;
use Throwable;

use function call_user_func;

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
        // Better repeat that stuff than having any type of unwanted exception
        // trace steps.
        if (!$this->exceptionHandler) {
            return $this->middleware->__invoke($input);
        }

        try {
            return $this->middleware->__invoke($input);
        } catch (Exception $e) {
            return call_user_func($this->exceptionHandler, $e, $input);
        } catch (Throwable $e) {
            return call_user_func($this->exceptionHandler, $e, $input);
        }
    }

    /**
     * @return MiddlewareCollectionContract
     */
    public function middleware() : MiddlewareCollectionContract
    {
        return $this->middleware;
    }

    /**
     * @return callable
     */
    public function getExceptionHandler() : callable
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
    public function setExceptionHandler(callable $handler) : InputHandler
    {
        $this->exceptionHandler = $handler;
        return $this;
    }

    /**
     * Find the last created input by a middleware
     *
     * @param Input $input
     * @return Input
     */
    protected function findLastCreatedInput(Input $input) : Input
    {
        if ($input instanceof ImmutableMessage && $input->next instanceof Input) {
            return $this->findLastCreatedInput($input->next);
        }
        return $input;
    }

}