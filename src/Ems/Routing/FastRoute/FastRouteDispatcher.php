<?php
/**
 *  * Created by mtils on 06.07.19 at 18:51.
 **/

namespace Ems\Routing\FastRoute;


use Ems\Contracts\Core\Type;
use Ems\Contracts\Routing\Exceptions\MethodNotAllowedException;
use Ems\Contracts\Routing\Exceptions\RouteNotFoundException;
use Ems\Contracts\Routing\Dispatcher;
use Ems\Contracts\Routing\RouteHit;
use Ems\Core\Exceptions\DataIntegrityException;
use Ems\Core\Exceptions\UnsupportedUsageException;
use Ems\Routing\CurlyBraceRouteCompiler;
use FastRoute\DataGenerator;
use FastRoute\Dispatcher as FastRouteDispatcherContract;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\RouteParser\Std;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use FastRoute\Dispatcher\CharCountBased as CharCountBasedDispatcher;
use FastRoute\Dispatcher\GroupPosBased as GroupPosBasedDispatcher;
use FastRoute\Dispatcher\MarkBased as MarkBasedDispatcher;
use function implode;


class FastRouteDispatcher implements Dispatcher
{
    /**
     * @var DataGenerator
     */
    protected $dataGenerator;

    /**
     * @var RouteCollector
     */
    protected $collector;

    /**
     * @var FastRouteDispatcherContract
     */
    protected $dispatcher;

    /**
     * @var CurlyBraceRouteCompiler
     */
    protected $compiler;

    /**
     * @var array
     */
    protected $data;

    public function __construct(DataGenerator $dataGenerator=null, CurlyBraceRouteCompiler $compiler=null)
    {
        $this->dataGenerator = $dataGenerator ?: new DataGenerator\GroupCountBased();
        $this->collector = $this->createCollector(new Std(), $this->dataGenerator);
        $this->compiler = $compiler ?: new CurlyBraceRouteCompiler();
    }

    /**
     * {@inheritDoc}
     *
     * @param string|string[] $method
     * @param string          $pattern
     * @param mixed           $handler
     */
    public function add($method, $pattern, $handler)
    {
        $handler = [
            'handler' => $handler,
            'pattern' => $pattern
        ];
        $this->collector->addRoute($method, $pattern, $handler);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $method
     * @param string $uri
     *
     * @return RouteHit
     */
    public function match($method, $uri)
    {
        $routeInfo = $this->dispatcher()->dispatch($method, $uri);

        if ($routeInfo[0] === FastRouteDispatcherContract::NOT_FOUND) {
            throw new RouteNotFoundException("No route did match uri '$uri'");
        }

        if ($routeInfo[0] === FastRouteDispatcherContract::METHOD_NOT_ALLOWED) {
            $allowedMethods = $routeInfo[1];
            throw new MethodNotAllowedException("Method $method is not allowed on uri '$uri' only " . implode(',', $allowedMethods));
        }

        if ( !isset($routeInfo[1]['handler']) || !isset($routeInfo[1]['pattern'])) {
            throw new DataIntegrityException('The data is broken is missing pattern and handler. Either it was not build by or is broken.');
        }

        $parameters = (isset($routeInfo[2]) && $routeInfo[2]) ? $routeInfo[2] : [];

        return new RouteHit($method, $routeInfo[1]['pattern'], $routeInfo[1]['handler'], $parameters);
    }

    /**
     * {@inheritDoc}
     *
     * @param array $data
     *
     * @return bool
     */
    public function fill(array $data)
    {
        $this->data = $data;
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     **/
    public function toArray()
    {
        return $this->collector->getData();
    }

    /**
     * {@inheritDoc}
     *
     * @param string $pattern
     * @param array $parameters (optional)
     *
     * @return string
     */
    public function path($pattern, array $parameters = [])
    {
        return $this->compiler->compile($pattern, $parameters);
    }

    /**
     * @return FastRouteDispatcherContract
     */
    protected function dispatcher()
    {
        if ($this->dispatcher) {
            return $this->dispatcher;
        }
        $this->dispatcher = $this->createDispatcher($this->data ?: $this->collector->getData());
        return $this->dispatcher;
    }

    /**
     * @param RouteParser $parser
     * @param DataGenerator $dataGenerator
     *
     * @return RouteCollector
     */
    protected function createCollector(RouteParser $parser, DataGenerator $dataGenerator)
    {
        return new RouteCollector($parser, $dataGenerator);
    }

    /**
     * @param array $data
     *
     * @return FastRouteDispatcherContract
     */
    protected function createDispatcher(array $data)
    {
        if ($this->dataGenerator instanceof DataGenerator\GroupCountBased) {
            return new GroupCountBasedDispatcher($data);
        }

        if ($this->dataGenerator instanceof DataGenerator\CharCountBased) {
            return new CharCountBasedDispatcher($data);
        }

        if ($this->dataGenerator instanceof DataGenerator\GroupPosBased) {
            return new GroupPosBasedDispatcher($data);
        }

        if ($this->dataGenerator instanceof DataGenerator\MarkBased) {
            return new MarkBasedDispatcher($data);
        }

        throw new UnsupportedUsageException('Cannot create Dispatcher for DataGenerator ' . Type::of($this->dataGenerator));
    }
}