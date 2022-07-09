<?php
/**
 *  * Created by mtils on 27.02.2022 at 07:57.
 **/

namespace Ems\Routing;

use App\Controllers\UserController;
use Closure;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\RouteRegistry as RouteRegistryContract;
use Ems\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Ems\RoutingTrait;
use Ems\TestCase;

class UrlGeneratorTest extends TestCase
{
    use RoutingTrait;

    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(UrlGeneratorContract::class, $this->make());
    }

    /**
     * @test
     */
    public function to_returns_path_plus_url()
    {
        $urls = $this->make();
        $domain = 'https://web-utils.de';
        $provider = $this->urlProvider($domain);
        $urls->setBaseUrlProvider($provider);

        $myData = $urls->to('my/data');
        $this->assertInstanceOf(Url::class, $myData);
        $this->assertEquals("$domain/my/data", "$myData");

        $home = $urls->to('/');
        $this->assertInstanceOf(Url::class, $home);
        $this->assertEquals("$domain", "$home");

    }

    /**
     * @test
     */
    public function to_returns_path_on_different_scope()
    {
        $urls = $this->make();
        $domain = 'https://web-utils.de';
        $provider = $this->urlProvider($domain);
        $urls->setBaseUrlProvider($provider);

        $myData = $urls->to('my/data', 'admin');

        $this->assertInstanceOf(Url::class, $myData);
        $this->assertEquals("https://admin.web-utils.de/my/data", "$myData");

        $home = $urls->to('/', 'en');
        $this->assertInstanceOf(Url::class, $home);
        $this->assertEquals("https://en.web-utils.de", "$home");

    }

    /**
     * @test
     */
    public function route_returns_url_to_route()
    {
        $urls = $this->make();
        $domain = 'https://web-utils.de';
        $provider = $this->urlProvider($domain);
        $urls->setBaseUrlProvider($provider);

        $users = $urls->route('users.index');
        $this->assertInstanceOf(Url::class, $users);
        $this->assertEquals("$domain/users", (string)$users);

        $createUser = $urls->route('users.create');
        $this->assertInstanceOf(Url::class, $createUser);
        $this->assertEquals("$domain/users/create", (string)$createUser);

        $showUser = $urls->route('users.show', [12]);
        $this->assertInstanceOf(Url::class, $showUser);
        $this->assertEquals("$domain/users/12", (string)$showUser);

        $storeUser = $urls->route('users.store');
        $this->assertInstanceOf(Url::class, $storeUser);
        $this->assertEquals("$domain/users", (string)$storeUser);

        $updateUser = $urls->route('users.update', [33]);
        $this->assertInstanceOf(Url::class, $updateUser);
        $this->assertEquals("$domain/users/33", (string)$updateUser);

        $deleteUser = $urls->route('users.destroy', [35]);
        $this->assertInstanceOf(Url::class, $deleteUser);
        $this->assertEquals("$domain/users/35", (string)$deleteUser);

    }

    /**
     * @test
     */
    public function route_returns_url_to_route_in_different_scope()
    {
        $urls = $this->make();
        $domain = 'https://web-utils.de';
        $provider = $this->urlProvider($domain);
        $urls->setBaseUrlProvider($provider);

        $users = $urls->route('users.index', [],'webservice');
        $this->assertInstanceOf(Url::class, $users);
        $this->assertEquals("https://webservice.web-utils.de/users", (string)$users);

    }

    /**
     * @test
     */
    public function route_works_with_multiple_parameters()
    {
        $registry = $this->registry();
        $registry->register(function (RouteCollector $collector) {
            $collector->get('users/{user_id}/projects/{project_id}/categories/{category_id}', UserController::class)
                ->name('users.projects.categories.show');
        });
        $urls = $this->make($registry);
        $domain = 'https://web-utils.de';
        $provider = $this->urlProvider($domain);
        $urls->setBaseUrlProvider($provider);

        $users = $urls->route('users.projects.categories.show', [12,55,8]);
        $this->assertInstanceOf(Url::class, $users);
        $this->assertEquals("$domain/users/12/projects/55/categories/8", (string)$users);

    }

    /**
     * @test
     */
    public function entity_returns_route_to_entity()
    {
        $registry = $this->registry();
        $registry->register(function (RouteCollector $collector) {
            $collector->get('projects/{project_id}',UrlGeneratorTest_ProjectController::class.'->show')
                ->entity(UrlGeneratorTest_Project::class, 'show');
        });
        $urls = $this->make($registry);
        $domain = 'https://web-utils.de';
        $provider = $this->urlProvider($domain);
        $urls->setBaseUrlProvider($provider);

        $project = new UrlGeneratorTest_Project();
        $showProject = $urls->entity($project);
        $this->assertEquals("$domain/projects/12", "$showProject");

        $this->assertEquals("$domain/projects/12", $urls->to($project));

    }

    /**
     * @test
     */
    public function asset_returns_base_url()
    {
        $urls = $this->make();
        $domain = 'https://web-utils.de';
        $provider = $this->urlProvider($domain);
        $urls->setBaseUrlProvider($provider);
        $this->assertSame($provider, $urls->getBaseUrlProvider());

        $this->assertEquals("$domain/blank.gif", $urls->asset('blank.gif'));
    }

    /**
     * @test
     */
    public function asset_uses_custom_asset_url()
    {
        $urls = $this->make();
        $domain = 'https://web-utils.de';
        $assetUrl = new \Ems\Core\Url('https://static.web-utils.de');
        $provider = $this->urlProvider($domain);
        $urls->setBaseUrlProvider($provider);
        $urls->setAssetUrl($assetUrl);

        $this->assertEquals("$assetUrl/blank.gif", $urls->asset('blank.gif'));
    }

    /**
     * @test
     */
    public function url_from_different_clientType()
    {
        $registry = $this->registry();
        $registry->register(function (RouteCollector $collector) {
            $collector->get('projects/{project_id}', UserController::class)
                ->name('projects.show')
            ->clientType([Input::CLIENT_WEB, Input::CLIENT_API]);
        });
        $urls = $this->make($registry);
        $domain = 'https://web-utils.de';
        $provider = function (Input $input, $scope = null) use ($domain) {
            $url = new \Ems\Core\Url($domain);
            if ($input->getClientType() == Input::CLIENT_WEB) {
                return $url;
            }
            return $url->append($input->getClientType());
        };

        $otherClientInput = new GenericInput();
        $otherClientInput->setClientType(Input::CLIENT_API);
        $urls->setBaseUrlProvider($provider);

        $this->assertEquals("$domain/projects/33",  (string)$urls->route('projects.show', [33]));
        $this->assertEquals("$domain/api/projects/33",  (string)$urls->withInput($otherClientInput)->route('projects.show', [33]));

    }

    protected function make(RouteRegistryContract $registry=null, CurlyBraceRouteCompiler $compiler=null, Input $input=null, &$baseUrlCache=[]) : UrlGenerator
    {
        $registry = $registry ?: $this->registry(true);
        $compiler = $compiler ?: new CurlyBraceRouteCompiler();
        return new UrlGenerator($registry, $compiler, $input, $baseUrlCache);
    }

    protected function urlProvider($baseUrl = 'http://localhost') : Closure
    {
        return function (Input $input, $scope=null) use ($baseUrl) {
            $baseUrl = $baseUrl instanceof Url ? $baseUrl : new \Ems\Core\Url($baseUrl);
            if ($scope === null) {
                return $baseUrl;
            }
            return $baseUrl->host("$scope.$baseUrl->host");
        };
    }
}

class UrlGeneratorTest_Project
{
    public function getId()
    {
        return 12;
    }
}

class UrlGeneratorTest_ProjectController
{
    public function show($id)
    {

    }
}