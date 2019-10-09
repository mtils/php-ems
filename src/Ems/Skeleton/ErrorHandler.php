<?php
/**
 *  * Created by mtils on 25.08.19 at 12:32.
 **/

namespace Ems\Skeleton;

use Ems\Console\ArgvInput;
use Ems\Console\ConsoleOutputConnection;
use Ems\Contracts\Core\Exceptions\Termination;
use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\IO;
use Ems\Contracts\Core\Response as ResponseContract;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Routing\Exceptions\RouteNotFoundException;
use Ems\Core\Application;
use Ems\Core\Response;
use Ems\Http\Response as HttpResponse;
use Exception;
use Psr\Log\LoggerInterface as LoggerInterfaceAlias;

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
     * @param Exception $e
     * @param Input $input
     *
     * @return ResponseContract
     */
    public function handle(Exception $e, Input $input)
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
     * @param Exception $e
     * @param Input     $input
     *
     * @return ResponseContract
     */
    public function __invoke(Exception $e, Input $input)
    {
        return $this->handle($e, $input);
    }

    /**
     * @param Exception $e
     * @param Input     $input
     */
    protected function log(Exception $e, Input $input)
    {
        $this->logger()->error($e);
    }

    /**
     * Make a nice presentation of $e.
     *
     * @param Exception $e
     * @param Input     $input
     *
     * @return ResponseContract
     */
    protected function render(Exception $e, Input $input)
    {
        if ($input instanceof ArgvInput) {
            $this->renderConsoleException($e, $input);
            return new Response('');
        }
        return $this->makeResponse($e, $input);
    }

    /**
     * @param Exception $e
     * @param ArgvInput $input
     */
    protected function renderConsoleException(Exception $e, ArgvInput $input)
    {
        /** @var ConsoleOutputConnection $out */
        $out = $this->app->make(ConsoleOutputConnection::class);
        $out->line('<error>' . $e->getMessage() . '</error>');

        if(!$input->wantsVerboseOutput()) {
            return;
        }

        $out->write($e->getTraceAsString() . PHP_EOL);
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
     * @param Exception $e
     * @param Input     $input
     * @return bool
     */
    protected function shouldDisplayError(Exception $e, Input $input)
    {
        return $this->environment() != Application::PRODUCTION;
    }

    /**
     * Overwrite this method to control error logging
     *
     * @param Exception $e
     * @param Input     $input
     * @return bool
     */
    protected function shouldLogError(Exception $e, Input $input)
    {
        if ($e instanceof RouteNotFoundException) {
            return false;
        }
        return $this->environment() == Application::PRODUCTION;
    }

    /**
     * @param Exception $e
     *
     * @return bool
     */
    protected function isIgnored(Exception $e)
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
        /** @var IO $io */
        $io = $this->app->make(IO::class);
        return $io->log();
    }

    /**
     * @return string
     */
    protected function environment()
    {
        return $this->app->environment();
    }
}