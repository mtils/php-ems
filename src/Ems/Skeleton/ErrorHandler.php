<?php
/**
 *  * Created by mtils on 25.08.19 at 12:32.
 **/

namespace Ems\Skeleton;

use Ems\Console\ArgvInput;
use Ems\Console\ConsoleOutputConnection;
use Ems\Contracts\Core\Exceptions\Termination;
use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\Response as ResponseContract;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Routing\Exceptions\RouteNotFoundException;
use Ems\Core\Application;
use Ems\Core\Response;
use Ems\Http\Response as HttpResponse;
use Psr\Log\LoggerInterface as LoggerInterfaceAlias;
use Throwable;

use const PHP_EOL;

class ErrorHandler
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var array
     */
    protected $ignored = [
        Termination::class
    ];

    /**
     * ErrorHandler constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle the input.
     *
     * @param Throwable $e
     * @param Input $input
     *
     * @return ResponseContract
     */
    public function handle(Throwable $e, Input $input)
    {

        if ($this->isIgnored($e)) {
            return $this->makeResponse('', $input, 204);
        }

        if ($this->shouldLogError($e, $input)) {
            $this->log($e, $input);
        }

        if ($this->shouldDisplayError($e, $input)) {
            return $this->render($e, $input);
        }

        $shortClass = Type::short($e);

        return $this->makeResponse("No Content ($shortClass)", $input, 500);
    }

    /**
     * Use it as an input handler exception handler.
     *
     * @param Throwable $e
     * @param Input     $input
     *
     * @return ResponseContract
     */
    public function __invoke(Throwable $e, Input $input)
    {
        return $this->handle($e, $input);
    }

    /**
     * @param Throwable $e
     * @param Input     $input
     */
    protected function log(Throwable $e, Input $input)
    {
        $this->logger()->error($e);
    }

    /**
     * Make a nice presentation of $e.
     *
     * @param Throwable $e
     * @param Input     $input
     *
     * @return ResponseContract
     */
    protected function render(Throwable $e, Input $input)
    {
        if ($input instanceof ArgvInput) {
            $this->renderConsoleException($e, $input);
            return new Response('');
        }
        return $this->makeResponse($e, $input);
    }

    /**
     * @param Throwable $e
     * @param ArgvInput $input
     */
    protected function renderConsoleException(Throwable $e, ArgvInput $input)
    {
        /** @var ConsoleOutputConnection $out */
        $out = $this->app->get(ConsoleOutputConnection::class);
        $out->line('<error>' . $e->getMessage() . '</error>');

        if(!$input->wantsVerboseOutput()) {
            return;
        }

        $out->write($e->getTraceAsString() . PHP_EOL);

        $maxParents = 10;
        $current = $e;
        $loop = 1;
        while ($previous = $current->getPrevious()) {
            $out->line('<info>Previous Exception:</info>');
            $out->line('<error>' . $previous->getMessage() . '</error>');
            $out->write($previous->getTraceAsString());
            $current = $previous;
            $loop++;
            if ($loop >= $maxParents) {
                break;
            }
        }
    }

    /**
     * @param mixed $payload
     * @param Input $input
     * @param int   $status (default:500)
     *
     * @return ResponseContract
     */
    protected function makeResponse($payload, Input $input, $status=500)
    {

        $response = new HttpResponse();

        $response->setStatus($status);
        $response->setPayload($payload);

        return $response;

    }
    /**
     * Overwrite this method to control error display
     *
     * @param Throwable $e
     * @param Input     $input
     * @return bool
     */
    protected function shouldDisplayError(Throwable $e, Input $input)
    {
        return $this->environment() != Application::PRODUCTION;
    }

    /**
     * Overwrite this method to control error logging
     *
     * @param Throwable $e
     * @param Input     $input
     * @return bool
     */
    protected function shouldLogError(Throwable $e, Input $input)
    {
        if ($e instanceof RouteNotFoundException) {
            return false;
        }
        return $this->environment() == Application::PRODUCTION;
    }

    /**
     * @param Throwable $e
     *
     * @return bool
     */
    protected function isIgnored(Throwable $e)
    {
        foreach ($this->ignored as $abstract) {
            if ($e instanceof $abstract) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return LoggerInterfaceAlias
     */
    protected function logger()
    {
        /** @var LoggerInterfaceAlias $logger **/
        $logger = $this->app->get(LoggerInterfaceAlias::class);
        return $logger;
    }

    /**
     * @return string
     */
    protected function environment()
    {
        return $this->app->environment();
    }
}