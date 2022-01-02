<?php
/**
 *  * Created by mtils on 14.09.19 at 17:36.
 **/

namespace Ems\Contracts\Routing;


use Ems\Contracts\Core\Arrayable;
use Ems\Contracts\Core\Map;
use Ems\Core\Helper;
use Ems\Core\Support\ObjectReadAccess;
use LogicException;

use function explode;
use function is_array;
use function substr;
use function trim;

/**
 * Class Command
 *
 * A command is the console counterpart to a route. In EMS you define your
 * console commands like routes. The whole definition then lies in you "console
 * route" and you can use everything in your application as a command. The same
 * as with routes.
 *
 * Like in Ems\Contracts\Core\Url: Properties are for read access, methods for
 * write access.
 *
 * @property-read string     pattern The pattern/uri/name like import:run or migrate:status
 * @property-read Argument[] arguments The console arguments (./console config:get $argument1 $argument2)
 * @property-read Option[]   options The console options (./console assets:copy --option1 --option2=value
 * @property-read string     description
 * @property-read Route[]    routes An array of associated routes indexed by its patterns
 *
 * @package Ems\Contracts\Routing
 */
class Command implements Arrayable
{
    use ObjectReadAccess;

    /**
     * @var RouteCollector $routeCollector
     */
    protected $routeCollector;

    /**
     * @var array
     */
    protected $_properties = [
        'pattern'      => '',
        'arguments'    => [],
        'options'      => [],
        'description'  => '',
        'routes'       => []
    ];

    /**
     * Command constructor.
     *
     * @param string $pattern
     * @param string $description
     * @param RouteCollector|null $routeCollector
     */
    public function __construct($pattern, $description='', RouteCollector $routeCollector=null)
    {
        $this->setPattern($pattern);
        $this->description($description);
        $this->routeCollector = $routeCollector;
    }

    /**
     * Register an input argument. Repeat calls for multiple arguments.
     *
     * @param string|Argument|array $signature
     * @param string $description (optional)
     *
     * @return $this
     *
     * @example ./console import:run $file
     *
     * ->argument('file') // required argument
     * ->argument('file=/dev/null') // argument with default
     * ->argument('file?') // optional argument
     * ->argument('?file') // optional argument
     *
     */
    public function argument($signature, $description='')
    {
        // Many arguments at once?
        if (is_array($signature)) {
            Map::apply($signature, [$this, 'argument']);
            return $this;
        }

        if (!$signature instanceof Argument) {
            $signature = $this->parseArgumentSignature($signature);
            $signature->description = $description;
        }

        $this->_properties['arguments'][] = $signature;

        return $this;
    }

    /**
     * Register an input option. Repeat calls for multiple options.
     *
     * @param string|Option|array $signature
     * @param string $description (optional)
     * @param string $shortcut (optional)
     *
     * @return $this
     *
     * @example ./console queue:work
     *
     * ->option('silent', 'Makes it very silently', 's') // bool type, optional. Shortcut is "s".
     * ->option("retry=") //  value without default
     * ->option("retry=5") // value + default
     * ->option("!retry=5") // required option (in opposite to the naming "option"
     *
     */
    public function option($signature, $description='', $shortcut='')
    {
        // Many arguments at once?
        if (is_array($signature)) {
            Map::apply($signature, [$this, 'option']);
            return $this;
        }

        if (!$signature instanceof Option) {
            $signature = $this->parseOptionSignature($signature);
            $signature->description = $description;
            $signature->shortcut = $shortcut;
        }

        $this->_properties['options'][] = $signature;
        return $this;
    }

    /**
     * Set a description for the command.
     *
     * @param string $description
     *
     * @return $this
     */
    public function description($description)
    {
        $this->_properties['description'] = $description;
        return $this;
    }

    /**
     * Set the pattern/uri/name.
     * @example autoload:dump
     *
     * @param string $pattern
     *
     * @return $this
     */
    public function setPattern($pattern)
    {
        $this->_properties['pattern'] = $pattern;
        return $this;
    }

    /**
     * @param string $pattern
     * @param Route $route
     *
     * @return $this
     */
    public function setRoute($pattern, Route $route)
    {
        $this->_properties['routes'][$pattern] = $route;
        return $this;
    }

    /**
     * This is a performance related method. In this method
     * you should implement the fastest was to get every
     * key and value as an array.
     * Only the root has to be an array, it should not build
     * the array by recursion.
     *
     * @return array
     **/
    public function toArray()
    {
        return $this->_properties;
    }

    /**
     * Register a _new_ GET route to the collector using the same handler.
     *
     * @param string $pattern
     *
     * @return Route
     */
    public function get($pattern)
    {
        return $this->on(Input::GET, $pattern);
    }

    /**
     * Register a _new_ POST route to the collector using the same handler.
     *
     * @param string $pattern
     *
     * @return Route
     */
    public function post($pattern)
    {
        return $this->on(Input::POST, $pattern);
    }

    /**
     * Register a _new_ PUT route to the collector and using the same handler.
     *
     * @param string $pattern
     *
     * @return Route
     */
    public function put($pattern)
    {
        return $this->on(Input::PUT, $pattern);
    }

    /**
     * Register a _new_ DELETE route to the collector using the same handler.
     *
     * @param string $pattern
     *
     * @return Route
     */
    public function delete($pattern)
    {
        return $this->on(Input::DELETE, $pattern);
    }

    /**
     * Register a _new_ PATCH route to the collector and using the same handler.
     *
     * @param string $pattern
     *
     * @return Route
     */
    public function patch($pattern)
    {
        return $this->on(Input::PATCH, $pattern);
    }

    /**
     * Register a _new_ OPTIONS route to the collector using the same handler.
     *
     * @param string $pattern
     *
     * @return Route
     */
    public function options($pattern)
    {
        return $this->on(Input::OPTIONS, $pattern);
    }

    /**
     * Register a NEW route for the given $method using the same handler.
     * Input has one pattern, so if you add a route for a command you have
     * two routes, one with the command string as its pattern and one with the
     * route path.
     *
     * @param string|string[] $method
     * @param string $pattern
     *
     * @return Route
     */
    public function on($method, $pattern)
    {
        if (!$this->routeCollector) {
            throw new LogicException('No routeCreator was assigned to create a route in this command');
        }
        if (!isset($this->_properties['routes'][$this->pattern])) {
            throw new LogicException("The route was not assigned to find the handler.");
        }
        return $this->routeCollector->on($method, $pattern, $this->_properties['routes'][$this->pattern]->handler);
    }

    /**
     * @param string $signature
     *
     * @return Argument
     */
    protected function parseArgumentSignature($signature)
    {
        $isOptional = false;
        $default = null;
        $type = null;

        // If starts or ends with ? its optional
        if (Helper::startsWith($signature, '?') || Helper::endsWith($signature,'?')) {
            $isOptional = true;
            $signature = trim($signature, '?');
        }

        $parts = explode('=', $signature);
        $name = $parts[0];
        $default = $parts[1] ?? null;

        // If the default value contained a space we assume it is an array
        if ($default && Helper::contains(trim($default), ' ')) {
            $type = 'array';
            $default = explode(' ', $default);
        }

        if (Helper::endsWith($name, '*')) {
            $name = substr($name, 0, -1);
            $type = 'array';
        }

        return (new Argument())->fill([
            'name'      => $name,
            'required'  => !$isOptional,
            'type'      => $type ?: 'string',
            'default'   => $default
        ]);
    }

    /**
     * @param string $signature
     *
     * @return Option
     */
    protected function parseOptionSignature($signature)
    {
        $isRequired = false;
        $default = null;
        $type = null;

        // If starts with ! it is required
        if (Helper::startsWith($signature, '!')) {
            $isRequired = true;
            $signature = substr($signature, 1);
        }

        $parts = explode('=', $signature);
        $name = $parts[0];
        $default = $parts[1] ?? null;

        $type = $default === null ? 'bool' : null;

        if ($default == '*' || $default == '[]') {
            $type = 'array';
            $default = [];
        }

        return (new Option())->fill([
            'name'      => $name,
            'required'  => $isRequired,
            'type'      => $type ?: 'string',
            'default'   => $default
        ]);
    }
}