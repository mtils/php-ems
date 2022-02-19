<?php
/**
 *  * Created by mtils on 08.02.2022 at 21:40.
 **/

namespace Ems\Routing;

use Ems\Contracts\Core\Url;
use Ems\Core\Url as UrlObject;
use Ems\Contracts\Routing\RouteScope;
use Ems\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Ems\Contracts\Routing\Router as RouterContract;

use function call_user_func;
use function is_object;
use function method_exists;

class UrlGenerator implements UrlGeneratorContract
{
    /**
     * @var callable
     */
    protected $baseUrlProvider;

    /**
     * @var Url
     */
    private $baseUrl;

    /**
     * @var RouterContract
     */
    protected $router;

    /**
     * @var CurlyBraceRouteCompiler
     */
    protected $compiler;

    public function __construct()
    {
        $this->baseUrl = new UrlObject('localhost');
    }

    /**
     * @param string|object             $path
     * @param string|RouteScope|null    $scope
     * @return Url
     */
    public function to($path, $scope = null): Url
    {
        if ($this->looksLikeAnEntity($path)) {
            return $this->entity($path, 'show', $scope);
        }
        return $this->getBaseUrl($scope)->append((string)$path);
    }

    /**
     * @param string            $name
     * @param array             $parameters
     * @param string|RouteScope $scope
     * @return Url
     */
    public function route(string $name, array $parameters = [], $scope = null): Url
    {
        $route = $this->router->getByName($name);
        return $this->to($this->compiler->compile($route->pattern, $parameters));
    }

    public function entity($entity, string $action = 'show', $scope = null): Url
    {
        // TODO: Implement entity() method.
    }


    /**
     * @param string|RouteScope|null $scope
     * @return Url
     */
    public function getBaseUrl($scope=null) : Url
    {
        if (!$this->baseUrlProvider) {
            return $this->baseUrl;
        }
        return call_user_func($this->baseUrlProvider, $this->baseUrl, $scope);
    }

    public function setBaseUrl(Url $url) : UrlGenerator
    {
        $this->baseUrl = $url;
        return $this;
    }

    /**
     * @param $path
     * @return bool
     */
    protected function looksLikeAnEntity($path) : bool
    {
        return is_object($path) && (isset($path->id) || method_exists($path, 'getId'));
    }
}