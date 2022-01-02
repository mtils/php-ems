<?php
/**
 *  * Created by mtils on 25.08.19 at 12:32.
 **/

namespace Ems\Skeleton;

use Ems\Contracts\Core\Extendable;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Routing\Exceptions\RouteNotFoundException;
use Ems\Contracts\Routing\Input;
use Ems\Core\Patterns\ExtendableTrait;
use Ems\Core\Response;
use ErrorException;
use Throwable;

use function call_user_func;
use function error_get_last;
use function error_reporting;
use function get_class;
use function register_shutdown_function;
use function set_error_handler;
use function set_exception_handler;

class ErrorHandler implements Extendable
{
    use ExtendableTrait;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var callable
     */
    protected $renderer;

    /**
     * @var null|bool
     */
    protected $logDeprecated;

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
     * @param Input|null $input
     *
     * @return Response
     */
    public function handle(Throwable $e, Input $input=null)
    {

        $input = $input ?: $this->app->read();
        $class = get_class($e);

        if ($this->hasExtension($class)) {
            return $this->callExtension($class, [$e, $input]);
        }

        if ($this->shouldLogError($e, $input)) {
            $this->log($e);
        }

        if ($this->shouldDisplayError($e, $input)) {
            return $this->render($e, $input);
        }

        $shortClass = Type::short($e);

        return new Response(['payload' => "No Content ($shortClass)", 'status' => 500]);
    }

    /**
     * Use it as an input handler exception handler.
     *
     * @param Throwable  $e
     * @param Input|null $input
     *
     * @return Response
     */
    public function __invoke(Throwable $e, Input $input=null)
    {
        return $this->handle($e, $input);
    }

    /**
     * Install the error handler and register it in php
     */
    public function install()
    {
        error_reporting(-1);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handle']);
        register_shutdown_function([$this, 'checkShutdown']);
    }

    public function handleError(int $level, string $message, string $file = '', int $line = 0, array $context=[])
    {
        if (!(error_reporting() && $level)) {
            return;
        }
        if (in_array($level,[E_DEPRECATED, E_USER_DEPRECATED])) {
            $this->handleDeprecatedError($message, $file, $line, $context);
            return;
        }
        throw new ErrorException($message, 0, $level, $file, $line);
    }

    /**
     * test for errors on shutdown
     */
    public function checkShutdown()
    {
        if (!$error = error_get_last()) {
            return;
        }
        if (!in_array($error["type"], [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE])) {
            return;
        }
        $this->handle(new ErrorException(
            $error['message'], 0, $error['type'], $error['file'], $error['line']
        ));
    }

    /**
     * @return callable
     */
    public function getRenderer(): callable
    {
        if (!$this->renderer) {
            $this->renderer = new ExceptionRenderer();
        }
        return $this->renderer;
    }

    /**
     * @param callable $renderer
     * @return ErrorHandler
     */
    public function setRenderer(callable $renderer): ErrorHandler
    {
        $this->renderer = $renderer;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldLogDeprecated() : bool
    {
        if ($this->logDeprecated !== null) {
            return $this->logDeprecated;
        }
        return $this->app->environment() !== Application::PRODUCTION;
    }

    /**
     * @param bool $force
     * @return $this
     */
    public function forceLogOfDeprecated(bool $force=true) : ErrorHandler
    {
        $this->logDeprecated = $force;
        return $this;
    }

    /**
     * @param string $message
     * @param string $file
     * @param int $line
     * @param array $context
     * @return void
     */
    protected function handleDeprecatedError(string $message, string $file = '', int $line = 0, array $context=[])
    {
        if ($this->shouldLogDeprecated()) {
            $this->app->log('debug',"Deprecated: " . $message . " in $file($line)");
        }
    }

    /**
     * @param Throwable $e
     */
    protected function log(Throwable $e)
    {
        $this->app->log('error', $this->formatException($e) . "\n");
    }

    /**
     * @param Throwable $e
     * @return string
     */
    protected function formatException(Throwable $e) : string
    {
        $message = 'Uncaught exception ' . get_class($e) . ' in ' . $e->getFile() . '(' . $e->getLine() . '): ';
        if ($code = $e->getCode()) {
            $message .= "Error #$code ";
        }
        $message .= $e->getMessage();
        return $message;
    }

    /**
     * Make a nice presentation of $e.
     *
     * @param Throwable $e
     * @param Input     $input
     *
     * @return Response
     */
    protected function render(Throwable $e, Input $input) : Response
    {
        return call_user_func($this->getRenderer(), $e, $input);
    }

    /**
     * Overwrite this method to control error display
     *
     * @param Throwable $e
     * @param Input     $input
     * @return bool
     */
    protected function shouldDisplayError(Throwable $e, Input $input) : bool
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
    protected function shouldLogError(Throwable $e, Input $input) : bool
    {
        if ($e instanceof RouteNotFoundException) {
            return false;
        }
        return $this->environment() == Application::PRODUCTION;
    }

    /**
     * @return string
     */
    protected function environment()
    {
        return $this->app->environment();
    }

}