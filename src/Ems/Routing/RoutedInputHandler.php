<?php
/**
 *  * Created by mtils on 21.07.19 at 14:24.
 **/

namespace Ems\Routing;

use Ems\Console\ArgvInput;
use Ems\Contracts\Core\Input as InputContract;
use Ems\Core\Input;
use Ems\Contracts\Core\InputHandler as InputHandlerContract;
use Ems\Contracts\Core\Response;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Routing\Routable;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Lambda;
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
class RoutedInputHandler implements InputHandlerContract, SupportsCustomFactory
{
    use CustomFactorySupport;

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

        return $this->respond($input, $this->call($handler, $input));

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
     * @param Lambda        $handler
     * @param InputContract $input
     */
    protected function configureLambda(Lambda $handler, InputContract $input)
    {
        if ($this->_customFactory && !$handler->getInstanceResolver()) {
            $handler->setInstanceResolver($this->_customFactory);
        }
        // Manually bind the current input to explicitly use the input of this
        // application call
        $handler->bind(InputContract::class, $input);
        $handler->bind(ArgvInput::class, $input);
        $handler->bind(Input::class, $input);
    }
}