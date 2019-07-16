<?php
/**
 *  * Created by mtils on 19.08.18 at 13:22.
 **/

namespace Ems\Routing;


use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\Response;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Routing\Dispatcher as DispatcherContract;
use Ems\Contracts\Routing\Routable;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Core\Lambda;
use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Response as CoreResponse;
use Ems\Core\Support\CustomFactorySupport;
use Ems\Http\Response as HttpResponse;
use function array_values;
use function php_sapi_name;

class Router implements RouterContract, SupportsCustomFactory
{
    use CustomFactorySupport;
    use HookableTrait;

    /**
     * @var DispatcherContract
     */
    protected $dispatcher;

    public function __construct(DispatcherContract $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritDoc}
     *
     * @param Input $input
     *
     * @return Response
     *
     * @throws \ReflectionException
     */
    public function handle(Input $input)
    {
        ////////////////////////////////////////////////////////////////////////
        // PHASE DISPATCH
        ////////////////////////////////////////////////////////////////////////
        $this->callBeforeListeners(static::PHASE_DISPATCH, [$input]);
        $routeData = $this->dispatch($input);
        $this->callAfterListeners(static::PHASE_DISPATCH, [$routeData, $input]);

        ////////////////////////////////////////////////////////////////////////
        // PHASE ROUTE
        ////////////////////////////////////////////////////////////////////////
        $this->callBeforeListeners(static::PHASE_ROUTE, [$input, $routeData]);
        $this->route($input, $routeData);
        $this->callAfterListeners(static::PHASE_ROUTE, [$input, $routeData]);

        ////////////////////////////////////////////////////////////////////////
        // PHASE INSTANTIATE
        ////////////////////////////////////////////////////////////////////////
        $this->callBeforeListeners(static::PHASE_INSTANTIATE, [$routeData, $input]);
        $lambda = $this->instantiate($routeData);
        $this->callAfterListeners(static::PHASE_INSTANTIATE, [$lambda, $routeData, $input]);

        ////////////////////////////////////////////////////////////////////////
        // PHASE CALL
        ////////////////////////////////////////////////////////////////////////
        $this->callBeforeListeners(static::PHASE_CALL, [$lambda, $routeData, $input]);
        $result = $this->call($lambda, array_values($routeData->routeParameters()));
        $this->callAfterListeners(static::PHASE_CALL, [$result, $routeData, $input]);

        ////////////////////////////////////////////////////////////////////////
        // PHASE RESPOND
        ////////////////////////////////////////////////////////////////////////
        $this->callBeforeListeners(static::PHASE_RESPOND, [$result, $routeData, $input]);
        $response = $this->respond($input, $result);
        $this->callAfterListeners(static::PHASE_RESPOND, [$response, $routeData, $input]);

        return $response;
    }

    /**
     * @return DispatcherContract|Route[]
     */
    public function routes()
    {
        return $this->dispatcher;
    }

    /**
     * Use the router as a (ems) middleware
     *
     * @param Input    $input
     * @param callable $next
     *
     * @return mixed
     *
     * @throws \ReflectionException
     */
    public function __invoke(Input $input, callable $next)
    {
        $next($input);
        return $this->handle($input);
    }

    /**
     * Return an array of methodnames which can be hooked via
     * onBefore and onAfter.
     *
     * @return array
     **/
    public function methodHooks()
    {
        return [
            static::PHASE_DISPATCH,
            static::PHASE_ROUTE,
            static::PHASE_INSTANTIATE,
            static::PHASE_CALL,
            static::PHASE_RESPOND
        ];
    }

    /**
     * @param Input $input
     *
     * @return Routable
     */
    protected function dispatch(Input $input)
    {
        $clientType = $input->clientType();
        $routeScope = $input->routeScope();

        return $this->dispatcher->dispatch(
            $input->method(),
            $input->url()->path->toString(),
            $clientType ?: $this->guessClientType(),
            $routeScope ? (string)$routeScope : 'default'
        );
    }
    /**
     * Make the input "routed".
     *
     * @param Input    $input
     * @param Routable $routable
     */
    protected function route(Input $input, Routable $routable)
    {
        $input->setUrl($routable->url());
        $input->setMatchedRoute($routable->matchedRoute());
        $input->setRouteParameters($routable->routeParameters());
        if (!$input->clientType()) {
            $input->setClientType($routable->clientType());
        }
    }

    /**
     * @param Routable  $routeData
     *
     * @return Lambda
     *
     * @throws \ReflectionException
     */
    protected function instantiate(Routable $routeData)
    {
        $handler = $routeData->matchedRoute()->handler;

        $lambda = new Lambda($handler, $this->_customFactory);

        if ($this->_customFactory) {
            $lambda->autoInject(true, true);
        }

        return $lambda;
    }

    /**
     * @param callable $callable
     * @param array $parameters
     *
     * @return mixed
     */
    protected function call(callable $callable, array $parameters=[])
    {
        return Lambda::callFast($callable, $parameters);
    }

    /**
     * @param Input $input
     * @param mixed $result
     *
     * @return Response
     */
    protected function respond(Input $input, $result)
    {
        if($result instanceof Response) {
            return $result;
        }

        if (in_array($input->clientType(),[Routable::CLIENT_CONSOLE, Routable::CLIENT_TASK])) {
            return (new CoreResponse())->setPayload($result);
        }

        return (new HttpResponse())->setPayload($result);
    }

    /**
     * Guess the client type if the request does not has one.
     *
     * @return string
     */
    protected function guessClientType()
    {
        return php_sapi_name() == 'cli' ? Routable::CLIENT_CONSOLE : Routable::CLIENT_WEB;
    }

}