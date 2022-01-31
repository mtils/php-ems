<?php
/**
 *  * Created by mtils on 15.09.19 at 18:07.
 **/

namespace Ems\Routing;


use Ems\Console\ArgumentVector;
use Ems\Contracts\Core\None;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Routing\Argument;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Option;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteScope;
use Ems\Core\Exceptions\MissingArgumentException;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\ImmutableMessage;
use Ems\Core\Url as UrlObject;
use LogicException;

use function array_key_exists;
use function strpos;

/**
 * @property-read array         argv
 * @property-read array         arguments
 * @property-read array         options
 * @property-read Route|null    matchedRoute
 * @property-read callable|null handler
 * @property-read array         routeParameters
 * @property-read Url           url
 * @property-read string        method
 * @property-read string        clientType
 * @property-read RouteScope    routeScope
 * @property-read string        locale
 * @property-read string        determinedContentType
 * @property-read string        apiVersion
 */
class ArgvInput extends ImmutableMessage implements Input
{
    use InputTrait;

    /**
     * @var string
     */
    protected $method = '';

    /**
     * @var array
     */
    private $argv;

    private $arguments = [];
    private $options = [];

    private $parsed = false;

    public function __construct(array $argv=[], Url $url=null)
    {
        $this->method = Input::CONSOLE;
        $this->clientType = Input::CLIENT_CONSOLE;
        $this->determinedContentType = 'text/x-ansi';
        $this->url = $url;

        if ($argv === [] || !$this->isAssociative($argv)) {
            $this->setArgv($argv ?: $_SERVER['argv']);
            parent::__construct();
            return;
        }
        parent::__construct($argv);
    }

    /**
     * @return array
     */
    public function getArgv() : array
    {
        return $this->argv;
    }

    /**
     * @param array $argv
     * @return ArgvInput
     */
    public function setArgv(array $argv) : ArgvInput
    {
        $this->argv = $argv;
        $this->parsed = false;
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
    public function argument(string $name, $default=null)
    {
        $this->parseIfNotParsed();
        return $this->arguments[$name] ?? $default;
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
    public function option(string $name, $default=null)
    {
        $this->parseIfNotParsed();
        return $this->options[$name] ?? $default;
    }

    /**
     * @return bool
     */
    public function wantsVerboseOutput() : bool
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

    /**
     * @return Url
     */
    public function getUrl(): Url
    {
        if (!$this->url) {
            $this->url = $this->generateUrl($this->getArgv());
        }
        return $this->url;
    }


    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->custom)) {
            return parent::get($key);
        }
        if ($this->matchedRoute) {
            $this->parseIfNotParsed();
        }
        if (isset($this->routeParameters[$key])) {
            return $this->routeParameters[$key];
        }
        return $default;
    }

    public function offsetExists($offset) : bool
    {
        if (isset($this->custom[$offset])) {
            return true;
        }
        if ($this->matchedRoute) {
            $this->parseIfNotParsed();
        }
        return isset($this->routeParameters[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function toArray() : array
    {
        if ($this->matchedRoute) {
            $this->parseIfNotParsed();
        }
        $data = [];
        foreach ($this->custom as $key=>$value) {
            $data[$key] = $value;
        }
        foreach ($this->routeParameters as $key=>$value) {
            if (!isset($data[$key])) {
                $data[$key] = $value;
            }
        }
        return $data;
    }

    public function __get(string $key)
    {
        switch ($key) {
            case 'argv':
                return $this->getArgv();
            case 'arguments':
                $this->parseIfNotParsed();
                return $this->arguments;
            case 'options':
                $this->parseIfNotParsed();
                return $this->options;

        }
        $value = $this->getInputTraitProperty($key);
        if (!$value instanceof None) {
            return $value;
        }
        return parent::__get($key);
    }


    protected function parseIfNotParsed()
    {
        if ($this->parsed) {
            return;
        }

        if (!$this->argv) {
            throw new UnConfiguredException("The argv was not assigned.");
        }

        if (!$route = $this->getMatchedRoute()) {
            throw new UnConfiguredException('The route (command) has to be assigned before parsing the argv');
        }

        $av = $this->argumentVector($this->argv, $route);

        if (!$args = $av->arguments()) {
            $args = [$route->pattern];
        }

        $this->addArguments($args, $route->command->arguments);
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
            $this->routeParameters[$i] = $value;
            $this->routeParameters[$argument->name] = $value;

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
                $this->routeParameters[$option->name] = $option->default;
                continue;
            }

            $value = $nameExists ? $options[$option->name] : $options[$option->shortcut];
            $this->options[$option->name] = $value;
            $this->routeParameters[$option->name] = $value;

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

    protected function apply(array $attributes)
    {
        if (isset($attributes['argv'])) {
            $this->argv = $attributes['argv'];
        }
        $this->applyInputTrait($attributes);
        parent::apply($attributes);
    }

    protected function copyStateInto(array &$attributes)
    {
        if (!isset($attributes['argv'])) {
            $attributes['argv'] = $this->argv;
        }
        $this->copyInputTraitStateInto($attributes);
        parent::copyStateInto($attributes);
    }

    /**
     * @param array $argv
     * @return UrlObject
     */
    protected function generateUrl(array $argv) : UrlObject
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

        return new UrlObject("console:$command");
    }
}