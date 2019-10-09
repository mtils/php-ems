<?php
/**
 *  * Created by mtils on 15.09.19 at 18:07.
 **/

namespace Ems\Console;


use Ems\Contracts\Routing\Argument;
use Ems\Contracts\Routing\Option;
use Ems\Contracts\Routing\Route;
use Ems\Core\Exceptions\MissingArgumentException;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Input;
use LogicException;
use function array_key_exists;
use function strpos;

class ArgvInput extends Input
{
    /**
     * @var array
     */
    private $argv;

    private $arguments = [];
    private $options = [];

    private $parsed = false;

    public function __construct(array $argv=[])
    {
        parent::__construct();
        $this->argv = $argv ?: $_SERVER['argv'];
    }

    /**
     * @return array
     */
    public function getArgv()
    {
        return $this->argv;
    }

    /**
     * @param array $argv
     * @return ArgvInput
     */
    public function setArgv(array $argv)
    {
        $this->argv = $argv;
        return $this;
    }

    /**
     * Return the value of console argument named $name.
     * The input has to be routed to support that.
     *
     * @param string $name
     * @param mixed $default (optional)
     *
     * @return mixed
     */
    public function argument($name, $default=null)
    {
        $this->parseIfNotParsed();
        return isset($this->arguments[$name]) ? $this->arguments[$name] : $default;
    }

    /**
     * Return the value of console option named $name.
     * The input has to be routed to support that.
     *
     * @param string $name
     * @param mixed $default (optional)
     *
     * @return mixed|null
     */
    public function option($name, $default=null)
    {
        $this->parseIfNotParsed();
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    /**
     * @return bool
     */
    public function wantsVerboseOutput()
    {
        foreach ($this->getArgv() as $value) {
            if (strpos($value, '-v') === 0) {
                return true;
            }
            if ($value === '--verbose') {
                return true;
            }
        }
        return false;
    }

    protected function parseIfNotParsed()
    {
        if ($this->parsed) {
            return;
        }

        if (!$this->argv) {
            throw new UnConfiguredException("The argv was not assigned.");
        }

        if (!$route = $this->matchedRoute()) {
            throw new UnConfiguredException('The route (command) has to be assigned before parsing the argv');
        }

        $av = $this->argumentVector($this->argv, $route);

        $this->addArguments($av->arguments(), $route->command->arguments);

        $this->addOptions($av->options(), $route->command->options);

        $this->parsed = true;
    }

    /**
     * @param string[]   $arguments
     * @param Argument[] $definitions
     */
    protected function addArguments(array $arguments, array $definitions)
    {

        foreach ($definitions as $i=>$argument) {

            $exists = array_key_exists($i, $arguments);

            if (!$exists && $argument->required) {
                throw new MissingArgumentException("Argument $argument->name (#$i) is required");
            }

            $value = $exists ? $arguments[$i] : $argument->default;
            $this->arguments[$argument->name] = $value;
            $this->_attributes[$i] = $value;
            $this->_attributes[$argument->name] = $value;

        }
    }

    /**
     * @param string[] $options
     * @param Option[] $definitions
     */
    protected function addOptions(array $options, array $definitions)
    {

        foreach ($definitions as $i=>$option) {

            $shortCutExists = array_key_exists($option->shortcut, $options);
            $nameExists = array_key_exists($option->name, $options);

            $exists = $shortCutExists || $nameExists;

            if ($shortCutExists && $nameExists) {
                throw new LogicException("You used option $option->name and its shortcut $option->shortcut parallel. This leads to unexpected behaviour.");
            }

            if (!$exists && $option->required) {
                $short = $option->shortcut ? " (short:$option->shortcut)" : '';
                throw new MissingArgumentException("Argument $option->name$short is required");
            }

            if (!$shortCutExists && !$nameExists) {
                $this->options[$option->name] = $option->default;
                $this->_attributes[$option->name] = $option->default;
                continue;
            }

            $value = $nameExists ? $options[$option->name] : $options[$option->shortcut];
            $this->options[$option->name] = $value;
            $this->_attributes[$option->name] = $value;

        }
    }

    /**
     * @param array $argv
     * @param Route $route
     *
     * @return ArgumentVector
     */
    protected function argumentVector(array $argv, Route $route)
    {
        $vector = new ArgumentVector($argv);

        foreach ($route->command->options as $option) {
            if ($option->shortcut) {
                $vector->defineShortOption($option->shortcut, $option->type !== 'bool');
            }
        }

        return $vector;
    }

}