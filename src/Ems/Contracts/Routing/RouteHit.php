<?php
/**
 *  * Created by mtils on 06.07.19 at 09:58.
 **/

namespace Ems\Contracts\Routing;


use Ems\Contracts\Core\ArrayData;
use Ems\Core\Support\FastArrayDataTrait;
use Ems\Core\Support\ObjectReadAccess;

/**
 * Class RouteHit
 *
 * A RouteHit is a result of matching a route by a RouteMatcher. ArrayAccess
 * is o access the route parameters
 *
 * @package Ems\Contracts\Routing
 *
 * @property-read string $method     The (http) method which did apply
 * @property-read string $pattern    The pattern that was registered and did apply
 * @property-read mixed  $handler    The handler that was registered
 * @property-read array  $parameters The parsed parameters
 */
class RouteHit implements ArrayData
{
    use FastArrayDataTrait;
    use ObjectReadAccess;

    protected $_properties = [
        'method'    => '',
        'pattern'   => '',
        'handler'   => null,
        'parameters'=> []
    ];

    /**
     * RouteHit constructor.
     *
     *
     * @param string $method
     * @param string $pattern
     * @param mixed  $handler
     * @param array  $parameters (optional)
     */
    public function __construct($method, $pattern, $handler, $parameters=[])
    {
        $this->_properties = [
            'method'     => $method,
            'pattern'    => $pattern,
            'handler'    => $handler,
            'parameters' => $parameters
        ];
        $this->_attributes = $parameters;
    }

    /**
     * Overwritten because it would be confusing to only get the parameters if
     * you call toArray on the match.
     *
     * @return array
     *
     * @see \Ems\Contracts\Core\Arrayable
     **/
    public function toArray()
    {
        return $this->_properties;
    }

}