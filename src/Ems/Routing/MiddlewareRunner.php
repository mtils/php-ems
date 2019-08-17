<?php
/**
 *  * Created by mtils on 20.07.19 at 08:56.
 **/

namespace Ems\Routing;


use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\InputHandler as InputHandlerContract;
use Ems\Contracts\Core\Response;
use Ems\Contracts\Routing\Exceptions\NoInputHandlerException;
use Ems\Contracts\Routing\MiddlewareCollection as CollectionContract;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Lambda;
use LogicException;
use function array_merge;
use function call_user_func;
use function current;
use function next;

class MiddlewareRunner
{
    /**
     * @var CollectionContract
     */
    protected $collection;

    /**
     * @var string[]
     */
    protected $names = [];

    /**
     * @var array
     */
    protected $middlewares = [];

    /**
     * @var bool
     */
    protected $responseWasCreatedBy = '';

    /**
     * @var bool
     */
    protected $inputHandlerCalled = false;

    /**
     * @var bool
     */
    protected $inputHandlerSkipped = false;

    /**
     * MiddlewareRunner constructor.
     *
     * @param CollectionContract $collection
     * @param string[] $names
     */
    public function __construct(CollectionContract $collection, $names)
    {
        $this->collection = $collection;
        $this->names = $names;
        $this->buildMiddlewareStack();
    }

    /**
     * Call the middleware(s) and return its response.
     *
     * @param Input $input
     *
     * @return Response
     */
    public function __invoke(Input $input)
    {
        $entry = current($this->names);

        $middleware = $this->middlewares[$entry];
        next($this->names);

        // Here we will have the first response a callable did create
        if(!$response = $this->callMiddleware($entry, $middleware, $input, $this)) {
            throw new HandlerNotFoundException('No middleware returned a response');
        }

        if (!$this->responseWasCreatedBy) {
            $this->responseWasCreatedBy = $entry;
        }

        // if there is no remaining middleware just return it
        if (!$entry = current($this->names)) {
            return $response;
        }

        // Now give later assigned middleware the chance to modify the response
        if (!$this->inputHandlerCalled && !$this->inputHandlerSkipped) {
            $entry = $this->forwardBehindInputHandler();
        }

        if (!$entry) {
            return $response;
        }

        $middleware = $this->middlewares[$entry];

        next($this->names);

        // We just fake an earlier middleware that forwards the response
        $proxy = function () use ($response, $entry) {
            return $response;
        };

        return $this->callMiddleware($entry, $middleware, $input, $proxy);

    }

    protected function buildMiddlewareStack()
    {
        $this->middlewares = [];

        $inputHandlerCount = 0;

        foreach (array_values($this->names) as $name) {
            $this->middlewares[$name] = $this->collection->middleware($name);
            if ($this->middlewares[$name] instanceof InputHandlerContract) {
                $inputHandlerCount++;
            }
        }

        if (!$inputHandlerCount) {
            $names = implode(',', $this->names);
            throw new NoInputHandlerException("No InputHandler found in middlewares. The main response creator has to be instanceof InputHandler. Names: ($names)");
        }

        if ($inputHandlerCount != 1) {
            throw new LogicException('There has to be exactly one InputHandler in your middlewares.');
        }

    }

    protected function callMiddleware($entry, callable $middleware, Input $input, callable $next)
    {
        if ($middleware instanceof InputHandlerContract) {
            $this->inputHandlerCalled = true;
            return call_user_func($middleware, $input);
        }

        $parameters = array_merge([$input, $next], $this->collection->parameters($entry));

        return Lambda::callFast($middleware, $parameters);

    }

    /**
     * Skip all handlers until you hit the inputhandler then take the next
     * @return string
     */
    protected function forwardBehindInputHandler()
    {
        $found = false;
        while($entry = current($this->names)) {

            $middleware = $this->middlewares[$entry];
            if ($found) {
                $this->inputHandlerSkipped = true;
                return $entry;
            }
            if ($middleware instanceof InputHandlerContract) {
                $found = true;
            }
            next($this->names);
        }
        return "$entry";
    }

}