<?php
/**
 *  * Created by mtils on 15.09.19 at 18:00.
 **/

namespace Ems\Console;


use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\InputConnection;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Routing\Routable;
use Ems\Core\Connection\AbstractConnection;
use Ems\Core\Url;
use function strpos;

class ConsoleInputConnection extends AbstractConnection implements InputConnection
{
    /**
     * @var string
     */
    protected $uri = 'php://stdin';

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function isInteractive()
    {
        return true;
    }

    /**
     * Receive the input. Use the returned input to process one. Pass a callable
     * to "stream receive" input.
     *
     * @param callable|null $into
     *
     * @return Input
     */
    public function read(callable $into = null)
    {
        $input = $this->createInput($_SERVER['argv']);
        $url = $this->createUrl($_SERVER['argv']);

        $input->setUrl($url);

        if ($into) {
            $into($input);
        }
        return $input;
    }


    /**
     * @param UrlContract $url
     *
     * @return resource
     */
    protected function createResource(UrlContract $url)
    {
        return fopen($this->uri, 'r');
    }

    /**
     * @param array $argv
     *
     * @return ArgvInput
     */
    protected function createInput(array $argv)
    {
        $input = new ArgvInput($argv);
        return $input->setMethod(Routable::CONSOLE);
    }

    /**
     * @param array $argv
     *
     * @return UrlContract
     */
    protected function createUrl(array $argv)
    {
        $command = '';

        foreach ($argv as $i=>$arg) {
            // Skip php filename and options
            if ($i < 1 || strpos($arg, '-') === 0) {
                continue;
            }
            $command = $arg;
            break;
        }

        return new Url("console:$command");
    }
}