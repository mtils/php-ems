<?php
/**
 *  * Created by mtils on 15.09.19 at 18:00.
 **/

namespace Ems\Skeleton;


use Ems\Contracts\Routing\Input;
use Ems\Contracts\Skeleton\InputConnection;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Connection\AbstractConnection;
use Ems\Core\Url;

use Ems\Routing\ArgvInput;

use function fgets;
use function fopen;
use function strpos;
use function strtolower;

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
        if ($into) {
            $into($input);
        }
        return $input;
    }

    /**
     * Get something from terminal.
     *
     * @return string
     */
    public function interact(): string
    {
        $handle = fopen($this->uri, 'r');
        $input = trim(fgets($handle));
        fclose($handle);
        return $input;
    }

    /**
     * Return true if the user typed one of the passed values.
     *
     * @param string[] $yes
     *
     * @return bool
     */
    public function confirm($yes=['y','yes','1','true']) : bool
    {
        $input = $this->interact();
        return in_array(strtolower($input), $yes);
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
        return new ArgvInput($argv, $this->createUrl($argv));
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