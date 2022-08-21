<?php
/**
 *  * Created by mtils on 08.02.2022 at 21:40.
 **/

namespace Ems\Routing;

use Closure;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteRegistry;
use Ems\Contracts\Routing\RouteScope;
use Ems\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Ems\Core\Url as UrlObject;
use UnexpectedValueException;

use function call_user_func;
use function get_class;
use function is_object;
use function method_exists;

class UrlGenerator implements UrlGeneratorContract
{
    /**
     * @var RouteRegistry
     */
    protected $registry;

    /**
     * @var CurlyBraceRouteCompiler
     */
    protected $compiler;

    /**
     * @var callable
     */
    protected $baseUrlProvider;

    /**
     * @var Input
     */
    protected $input;

    /**
     * @var array
     */
    protected $baseUrlCache = [];

    /**
     * @var Url
     */
    protected $assetUrl;

    public function __construct(RouteRegistry $registry, CurlyBraceRouteCompiler $compiler, Input $input=null, &$baseUrlCache=[])
    {
        $this->registry = $registry;
        $this->compiler = $compiler;
        $this->baseUrlProvider = $this->defaultBaseUrlProvider();
        $this->input = $input ?: GenericInput::clientType(Input::CLIENT_WEB, RouteScope::DEFAULT);
        $this->baseUrlCache = $baseUrlCache;
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
        $baseUrl = $this->getBaseUrl($scope);
        return $path == '/' ? $baseUrl : $baseUrl->append((string)$path);
    }

    /**
     * @param string|Route              $route
     * @param array                     $parameters
     * @param string|RouteScope|null    $scope
     *
     * @return Url
     */
    public function route($route, array $parameters = [], $scope = null): Url
    {
        if (!$route instanceof Route) {
            $route = $this->registry->getByName($route, $this->input->getClientType());
        }
        return $this->to($this->compiler->compile($route->pattern, $parameters), $scope);
    }

    /**
     * @param object|array          $entity
     * @param string                $action   (optional)
     * @param string|RouteScope|null $scope
     *
     * @return Url
     */
    public function entity($entity, string $action = 'show', $scope = null): Url
    {
        $route = $this->registry->getByEntityAction($entity, $action, $this->input->getClientType());
        return $this->route($route, [$this->extractId($entity)], $scope);
    }

    /**
     * Return an asset url.
     *
     * @param string                 $path
     * @param RouteScope|string|null $scope
     * @return Url
     */
    public function asset(string $path, $scope = null): Url
    {
        if ($this->assetUrl) {
            return $this->assetUrl->append($path);
        }
        return $this->to($path, $scope);
    }

    /**
     * @return Input
     */
    public function getInput(): Input
    {
        return $this->input;
    }

    public function withInput(Input $input): UrlGeneratorContract
    {
        $copy = (new static($this->registry, $this->compiler, $input, $this->baseUrlCache))
            ->setBaseUrlProvider($this->baseUrlProvider);
        if ($this->assetUrl) {
            $copy->setAssetUrl($this->assetUrl);
        }
        return $copy;
    }

    /**
     * @param string|RouteScope|null $scope
     * @return Url
     */
    public function getBaseUrl($scope=null) : Url
    {
        $cacheId = $this->cacheId($this->input, $scope);
        if (!isset($this->baseUrlCache[$cacheId])) {
            $this->baseUrlCache[$cacheId] = call_user_func($this->baseUrlProvider, $this->input, $scope);
        }
        return $this->baseUrlCache[$cacheId];
    }

    /**
     * @return callable
     */
    public function getBaseUrlProvider(): callable
    {
        return $this->baseUrlProvider;
    }

    /**
     * Set the base url provider. The base url provider creates an url for the
     * assigned input.
     * Create your own base url provider to return different urls per input
     *
     * @param callable $baseUrlProvider
     * @return UrlGenerator
     */
    public function setBaseUrlProvider(callable $baseUrlProvider): UrlGenerator
    {
        $this->baseUrlProvider = $baseUrlProvider;
        return $this;
    }

    /**
     * @return Url|null
     */
    public function getAssetUrl(): ?Url
    {
        return $this->assetUrl;
    }

    /**
     * @param Url $assetUrl
     * @return UrlGenerator
     */
    public function setAssetUrl(Url $assetUrl): UrlGenerator
    {
        $this->assetUrl = $assetUrl;
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

    /**
     * @param object $entity
     * @return mixed
     */
    protected function extractId($entity)
    {
        if (isset($entity->id)) {
            return $entity->id;
        }
        if (method_exists($entity, 'getId')) {
            return $entity->getId();
        }
        $class = get_class($entity);
        throw new UnexpectedValueException("Impossible to guess identifier of object of class '$class'");
    }

    /**
     * @param Input                     $input
     * @param RouteScope|string|null    $scope
     *
     * @return string
     */
    protected function cacheId(Input $input, $scope=null) : string
    {
        $scope = $scope ?: $input->getRouteScope();
        return $input->getClientType() . '|' . ($scope ?: RouteScope::DEFAULT);
    }

    /**
     * @return Closure
     */
    protected function defaultBaseUrlProvider() : Closure
    {
        return function (Input $input, $scope=null) {
            if ($url = $input->getUrl()) {
                return new UrlObject($url->scheme . '://' . $url->host);
            }
            return new UrlObject('http://localhost');
        };
    }
}