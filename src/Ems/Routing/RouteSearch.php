<?php
/**
 *  * Created by mtils on 05.02.2022 at 20:21.
 **/

namespace Ems\Routing;

use ArrayIterator;
use Ems\Contracts\Model\Search;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\Router as RouterContract;
use UnexpectedValueException;

use function array_key_exists;
use function array_keys;
use function func_num_args;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function strpos;

class RouteSearch implements Search
{
    public const METHODS = 'Methods';

    public const PATTERN = 'Pattern';

    public const NAME = 'Name';

    public const CLIENTS = 'Clients';

    public const SCOPES = 'Scopes';

    public const MIDDLEWARE = 'Middleware';

    /**
     * @var string[]
     */
    public const ALL_KEYS = [
        self::METHODS,
        self::PATTERN,
        self::NAME,
        self::CLIENTS,
        self::SCOPES,
        self::MIDDLEWARE
    ];

    /**
     * @var array
     */
    protected $input = [];

    /**
     * @var array
     */
    protected $filters=[];

    /**
     * @var string[]
     */
    protected $keys = [];

    /**
     * @var RouterContract
     */
    protected $router;

    /**
     * @var object|null
     */
    protected $creator;

    public function __construct(RouterContract $router)
    {
        $this->router = $router;
    }

    /**
     * @param array $input
     * @return $this|RouteSearch
     */
    public function apply(array $input)
    {
        $this->input = $input;
        $this->filters = [];
        return $this;
    }

    /**
     * @param $key
     * @param $default
     * @return array|mixed|null
     */
    public function input($key = null, $default = null)
    {
        if (!$key) {
            return $this->input;
        }
        return array_key_exists($key, $this->input) ? $this->input[$key] : $default;
    }

    /**
     * @param $key
     * @param $value
     * @return $this|RouteSearch
     */
    public function filter($key, $value = null)
    {
        $this->parseInput();
        $values = is_array($key) ? $key : [$key=>$value];
        foreach ($values as $key=>$value) {
            $this->filters[$key] = $value;
        }
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function hasFilter($key, $value = null)
    {
        $this->parseInput();

        if (!array_key_exists($key, $this->filters)) {
            return false;
        }

        if (func_num_args() < 2) {
            return true;
        }
        return $this->filters[$key] == $value;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function filterValue($key)
    {
        if ($this->hasFilter($key)) {
            return $this->filters[$key];
        }
        return null;
    }

    /**
     * @return string[]
     */
    public function filterKeys()
    {
        $this->parseInput();
        return array_keys($this->filters);
    }

    /**
     * @param $key
     * @return $this|RouteSearch
     */
    public function clearFilter($key = null)
    {
        if (!$key) {
            $this->filters = [];
            return $this;
        }
        if (isset($this->filters[$key])) {
            unset($this->filters[$key]);
        }
        return $this;
    }

    /**
     * @return string[]
     */
    public function keys() : array
    {
        return $this->keys ?: self::ALL_KEYS;
    }

    /**
     * @param array $keys
     * @return $this
     */
    public function setKeys(array $keys) : RouteSearch
    {
        foreach ($keys as $key) {
            if (!in_array($key, self::ALL_KEYS)) {
                throw new UnexpectedValueException("Unknown key $key. Allowed is only ".implode('', self::ALL_KEYS));
            }
        }
        $this->keys = $keys;
        return $this;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        $this->parseInput();
        $rows = [];
        /** @var Route $route */
        foreach ($this->router as $route) {
            if ($this->matches($route, $this->filters)) {
                $rows[] = $route;
            }
        }
        return new ArrayIterator($rows);
    }

    /**
     * @return object|null
     */
    public function creator()
    {
        return $this->creator;
    }

    /**
     * @param object $creator
     * @return $this
     */
    public function setCreator($creator)
    {
        if (!is_object($creator)) {
            throw new UnexpectedValueException('Creator has to be an object');
        }
        $this->creator = $creator;
        return $this;
    }

    /**
     * @return Route|null
     */
    public function first()
    {
        /** @var Route $route */
        foreach ($this as $route) {
            return $route;
        }
        return null;
    }

    /**
     * @return Route|null
     */
    public function last()
    {
        $lastRoute = null;
        /** @var Route $route */
        foreach ($this as $route) {
            $lastRoute = $route;
        }
        return $lastRoute;
    }

    /**
     * @param Route $route
     * @param array $filter
     *
     * @return bool
     */
    protected function matches(Route $route, array $filter) : bool
    {
        if (!$this->match($filter['pattern'], $route->pattern)) {
            return false;
        }

        if (!$this->match($filter['name'], $route->name)) {
            return false;
        }

        if (!$this->match($filter['client'], $route->clientTypes)) {
            return false;
        }
        if (!$this->match($filter['scope'], $route->scopes)) {
            return false;
        }
        return true;
    }

    /**
     * @param string          $pattern
     * @param string|string[] $item
     *
     * @return bool
     */
    protected function match(string $pattern, $item) : bool
    {
        if ($pattern == '*') {
            return true;
        }
        if (!is_array($item)) {
            return strpos($item, $pattern) !== false;
        }
        foreach ($item as $string) {
            if ($this->match($pattern, $string)) {
                return true;
            }
        }
        return false;
    }

    protected function parseInput()
    {
        if ($this->filters) {
            return;
        }

        $this->filters = [
            'pattern' => '*',
            'client'  => '*',
            'name'    => '*',
            'scope'   => '*',
        ];

        foreach ($this->filters as $key=>$value) {
            if (isset($this->input[$key]) && $this->input[$key]) {
                $this->filters[$key] = $this->input[$key];
            }
        };

    }

}