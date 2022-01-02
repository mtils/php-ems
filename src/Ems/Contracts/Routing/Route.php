<?php
/**
 *  * Created by mtils on 16.08.18 at 15:14.
 **/

namespace Ems\Contracts\Routing;


use LogicException;
use function array_values;
use Ems\Contracts\Core\Arrayable;
use Ems\Core\Support\ObjectReadAccess;
use function func_get_args;
use function is_array;

/**
 * Class Route
 *
 * Objects of this class hold information about one route.
 * A Route is just a value object and does not contain a lot of processing.
 * All processing has to be done by the dispatcher or middlewares.
 * With this objects you are able to assign routes by a fluent interface:
 *
 * Registrar::get('users', function () {})->name('users.index')
 *
 * Like in Ems\Contracts\Core\Url: Properties are for read access, methods for
 * write access.
 *
 * @package Ems\Contracts\Routing
 *
 * @property-read array     $methods     (http)-methods The assigned methods of this route
 * @property-read string    $pattern     The route pattern
 * @property-read mixed     $handler     The assigned (whatever) handler
 * @property-read string    $name        The unique name of this route
 * @property-read array     $middlewares The middlewares of this route
 * @property-read string[]  $clientTypes The types of client which have access to this route
 * @property-read string[]  $scopes      The RouteScopes in which this route applies
 * @property-read array     $defaults    The default route parameters
 * @property-read Command   command      An associated console command (or null)
 */
class Route implements Arrayable
{
    use ObjectReadAccess;

    /**
     * @var array
     */
    protected $_properties = [
        'methods'     => [],
        'pattern'     => '',
        'handler'     => null,
        'name'        => '',
        'middlewares' => [],
        'clientTypes' => [],
        'scopes'      => [],
        'defaults'    => [],
        'command'     => null
    ];

    /**
     * @var RouteCollector
     */
    protected $collector;

    /**
     * RouteConfiguration constructor.
     *
     * @param string|array $method
     * @param string       $pattern
     * @param mixed        $handler
     * @param RouteCollector|null $collector
     */
    public function __construct($method, string $pattern, $handler, RouteCollector $collector=null)
    {
        $this->setMethod($method);
        $this->setPattern($pattern);
        $this->setHandler($handler);
        $this->collector = $collector;
    }

    /**
     * Give this route name to reference it (by an url generator)
     * @param string $name
     *
     * @return $this
     */
    public function name($name)
    {
        $this->_properties['name'] = $name;
        return $this;
    }

    /**
     * Determine that $middleware should be executed before running this route.
     * A string assigns one middleware, many strings or an array assigns multiple
     * middlewares.
     * If you just call middleware() without a parameter it resets the middleware
     * to no middleware for this route.
     * You can also call it multiple times for multiple middleware.
     *
     * @param string|array|null $middleware (optional)
     *
     * @return $this
     */
    public function middleware($middleware=null)
    {
        if ($middleware === null) {
            $this->_properties['middlewares'] = [];
            return $this;
        }

        $middlewares = is_array($middleware) ? array_values($middleware) : func_get_args();

        foreach ($middlewares as $singleMiddleware) {
            $this->_properties['middlewares'][] = $singleMiddleware;
        }
        return $this;
    }

    /**
     * Let this route match only to distinct client types.
     * Client types are: api|console|web|cms|desktop|mobile|whatever
     *
     * @param string|array $type
     *
     * @return $this
     */
    public function clientType($type)
    {
        $this->_properties['clientTypes'] = is_array($type) ? $type : func_get_args();
        return $this;
    }

    /**
     * Restrict this route to just be callable in scope.
     *
     * @param string|RouteScope|array $scope
     *
     * @return $this
     */
    public function scope($scope)
    {
        $this->_properties['scopes'] = is_array($scope) ? $scope : func_get_args();
        return $this;
    }

    /**
     * Set some parameter defaults.
     *
     * @param string|array $key
     * @param mixed        $value (optional)
     *
     * @return $this
     */
    public function defaults($key, $value=null)
    {
        if (is_array($key)) {
            $this->_properties['defaults'] = $key;
            return $this;
        }
        $this->_properties['defaults'][$key] = $value;
        return $this;
    }

    /**
     * Associate a console command with this route. Pass a string to register
     * a new command pointing to the same handler.
     * This will create a second route because the command as another pattern.
     *
     * @param Command|string $command
     * @param string $description (optional)
     *
     * @return Command
     */
    public function command($command, $description='')
    {
        if ($command instanceof Command) {
            $this->_properties['command'] = $command;
            if ($this->pattern) {
                $command->setRoute($this->pattern, $this);
            }
            return $command;
        }

        if (!$this->collector) {
            throw new LogicException("No collector assigned to this route to register a new command.");
        }

        return $this->collector->command($command, $this->handler, $description);
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     **/
    public function toArray()
    {
        return $this->_properties;
    }

    /**
     * Assign the http method(s) which should handled by this route
     *
     * @param array|string $method
     */
    protected function setMethod($method)
    {
        $this->_properties['methods'] = is_array($method) ? $method : func_get_args();
    }

    /**
     * Assign the url of this route.
     *
     * @param string $pattern
     */
    protected function setPattern(string $pattern)
    {
        $this->_properties['pattern'] = $pattern;
    }

    /**
     * Assign a handler for this route
     *
     * @param mixed $handler
     */
    protected function setHandler($handler)
    {
        $this->_properties['handler'] = $handler;
    }

}
