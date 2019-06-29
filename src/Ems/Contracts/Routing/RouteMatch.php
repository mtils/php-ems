<?php
/**
 *  * Created by mtils on 19.08.18 at 10:47.
 **/

namespace Ems\Contracts\Routing;


use Ems\Contracts\Core\ArrayData;
use Ems\Contracts\Core\Url;
use Ems\Core\Support\FastArrayDataTrait;
use Ems\Core\Support\ObjectReadAccess;

/**
 * Class RouteMatch
 *
 * A RouteMatch is a match that the Routing\Dispatcher.
 * Like route access to pseudo public properties it is used to access its properties.
 * Route parameters are available through ArrayAccess.
 *
 * @package Ems\Contracts\Routing
 *
 * @property-read string $method     The (http) method which did apply
 * @property-read Url    $url        The url that matched the route
 * @property-read Route  $route      The matching route
 * @property-read array  $parameters The parameters (You also get them by toArray()
 */
class RouteMatch implements ArrayData
{
    use FastArrayDataTrait;
    use ObjectReadAccess;

    protected $_properties = [
        'method'     => '',
        'url'        => null,
        'route'      => null,
        'parameters' => []
    ];

    /**
     * RouteMatch constructor.
     *
     * @param Route  $route
     * @param string $method
     * @param Url    $url
     * @param array  $parameters (optional)
     */
    public function __construct(Route $route, $method, Url $url, array $parameters=[])
    {
        $this->_properties = [
            'route' => $route,
            'method' => $method,
            'url' => $url,
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