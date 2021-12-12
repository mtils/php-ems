<?php
/**
 *  * Created by mtils on 21.07.19 at 14:24.
 **/

namespace Ems\Routing;

use Ems\Console\ArgvInput;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Core\Input as InputContract;
use Ems\Contracts\Routing\InputHandler as InputHandlerContract;
use Ems\Contracts\Core\IOCContainer;
use Ems\Contracts\Core\Response;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Core\UtilizesInput;
use Ems\Contracts\Routing\Routable;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Input;
use Ems\Core\Lambda;
use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Response as CoreResponse;
use Ems\Core\Support\CustomFactorySupport;
use Ems\Http\Response as HttpResponse;

use function is_callable;

/**
 * Class RoutedInputHandler
 *
 * This class does the actual running of the route handler.
 * The Input has to be routed before calling __invoke. Otherwise it will just
 * throw an exception.
 *
 * @package Ems\Routing
 */
class RoutedInputHandler implements InputHandlerContract, SupportsCustomFactory, HasMethodHooks
{
    use CustomFactorySupport;
    use HookableTrait;

    public function __construct(callable $customFactory=null)
    {
        $this->_customFactory = $customFactory;
    }

    /**
     * Handle the input and return a corresponding response
     *
     * @param InputContract $input
     *
     * @return Response
     */
    public function __invoke(InputContract $input)
    {
        if (!$input->isRouted()) {
            throw new UnConfiguredException('The input has to be routed to get handled by ' . static::class . '.');
        }

        $handler = $input->getHandler();

        if(!is_callable($handler)) {
            throw new UnConfiguredException('The input says it is routed but missing a callable handler. getHandler() returned a ' . Type::of($handler));
        }

        if ($handler instanceof Lambda) {
            $this->configureLambda($handler, $input);
        }

        $this->callBeforeListeners('call', [$handler, $input]);

        $response = $this->respond($input, $this->call($handler, $input));

        $this->callAfterListeners('call', [$handler, $input, $response]);

        return $response;

    }

    /**
     * {@inheritDoc}
     *
     * @return array
     **/
    public function methodHooks()
    {
        return ['call'];
    }


    /**
     * Call the handler.
     *
     * @param callable $handler
     * @param Routable $routable
     *
     * @return mixed
     */
    protected function call(callable $handler, Routable $routable)
    {
        return Lambda::callFast($handler, array_values($routable->routeParameters()));
    }

    /**
     * Create the response from the handlers output.
     *
     * @param InputContract $input
     * @param mixed $result
     *
     * @return Response
     */
    protected function respond(InputContract $input, $result)
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
     * @param Lambda $handler
     * @param InputContract $input
     * @throws \ReflectionException
     */
    protected function configureLambda(Lambda $handler, InputContract $input)
    {
        if ($this->_customFactory && !$handler->getInstanceResolver()) {
            $handler->setInstanceResolver($this->_customFactory);
        }
        if ($this->_customFactory instanceof IOCContainer) {
            $this->_customFactory->on(UtilizesInput::class, function (UtilizesInput $inputUser) use ($input) {
                $inputUser->setInput($input);
            });
        }
        // Manually bind the current input to explicitly use the input of this
        // application call
        $handler->bind(InputContract::class, $input);
        $handler->bind(Input::class, $input);
        if ($input instanceof ArgvInput) {
            $handler->bind(ArgvInput::class, $input);
        }

        if (!$handler->isInstanceMethod() || !$controller = $handler->getCallInstance()) {
            return;
        }

        if ($controller instanceof UtilizesInput) {
            $controller->setInput($input);
        }

    }
}