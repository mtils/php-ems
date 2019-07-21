<?php
/**
 *  * Created by mtils on 20.07.19 at 08:56.
 **/

namespace Ems\Routing;


use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\Response;
use Ems\Contracts\Routing\MiddlewareCollection as CollectionContract;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Lambda;
use function array_merge;
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
     * MiddlewareRunner constructor.
     *
     * @param CollectionContract $collection
     * @param string[] $names
     */
    public function __construct(CollectionContract $collection, $names)
    {
        $this->collection = $collection;
        $this->names = $names;
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
        if ($entry === false) {
            throw new HandlerNotFoundException('No middleware returned a response');
        }
        $middleware = $this->collection->middleware($entry);
        $parameters = $this->collection->parameters($entry);
        next($this->names);

        $allParameters = array_merge([$input, $this], $parameters);

        // Here we will have the first response a callable did create
        $response = Lambda::callFast($middleware, $allParameters);

        // if there is no remaining middleware just return it
        if (!$entry = current($this->names)) {
            return $response;
        }

        // Now give later assigned middleware the chance to modify the response
        $middleware = $this->collection->middleware($entry);
        $parameters = $this->collection->parameters($entry);
        next($this->names);

        // We just fake an earlier middleware that forwards the response
        $proxy = function () use ($response) {
            return $response;
        };

        $allParameters = array_merge([$input, $proxy], $parameters);

        return Lambda::callFast($middleware, $allParameters);

    }


}