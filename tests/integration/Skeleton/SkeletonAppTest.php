<?php
/**
 *  * Created by mtils on 05.03.2022 at 19:20.
 **/

namespace integration\Skeleton;

use DateTime;
use Ems\Contracts\Core\Arrayable;
use Ems\Contracts\Core\ConnectionPool;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\ResponseFactory;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\Router;
use Ems\Contracts\Routing\UrlGenerator;
use Ems\DatabaseIntegrationTest;
use Ems\Model\Orm;
use Ems\Model\Skeleton\OrmBootstrapper;
use Ems\RoutingTrait;
use Ems\Skeleton\Application;
use Ems\Skeleton\Testing\HttpCalls;
use Ems\TestOrm;
use Models\Ems\ProjectTypeMap;
use Models\Project;
use Models\ProjectType;
use Models\User;

use function get_class;
use function is_object;
use function iterator_to_array;
use function json_encode;

class SkeletonAppTest extends DatabaseIntegrationTest
{
    use RoutingTrait;
    use TestOrm;
    use HttpCalls;

    protected $extraBootstrappers = [OrmBootstrapper::class];

    /**
     * @test
     */
    public function create_ProjectType()
    {
        $response = $this->post('/project-types', ['name' => 'TestType']);
        $this->assertEquals('application/json', $response->contentType);
        $data = iterator_to_array($response);
        $id = $data['id'];
        $this->assertGreaterThan(0, $id);
        $this->assertEquals('TestType', $data['name']);

        $headers = $response->envelope;
        $this->assertContains("project-types/$id", $headers['Link']);

        /** @var Orm $orm */
        $orm = $this->app(Orm::class);
        $type = $orm->query(ProjectType::class)->where('id', $id)->first();
        $this->assertInstanceOf(ProjectType::class, $type);
    }

    /**
     * @test
     */
    public function list_ProjectTypes()
    {
        $response = $this->get('/project-types');
        $data = iterator_to_array($response);
        $this->assertNotEmpty($data);

        foreach ($data as $typeArray) {
            $this->assertGreaterThan(0, $typeArray['id']);
            $this->assertNotEmpty($typeArray['name']);
            $this->assertNotEmpty($typeArray['created_at']);
            $this->assertNotEmpty($typeArray['updated_at']);
        }
    }

    /**
     * @test
     */
    public function create_Project()
    {
        /** @var Orm $orm */
        $orm = $this->app(Orm::class);
        /** @var ProjectType $type */
        $type = $orm->query(ProjectType::class)->last();

        $response = $this->post('/projects', [
            'name'      => 'TestProject',
            'owner'     => ['id' => 1],
            'type'      => ['id' => $type->id]
        ]);

        $data = iterator_to_array($response);
        $headers = $response->envelope;
        $this->assertContains("projects/".$data['id'], $headers['Link']);
        $this->assertEquals('TestProject', $data['name']);
    }

    protected function configureApplication(Application $app)
    {
        $ormDir = static::dirOfTests('database/orm');
        $namespace = "Models";

        $app->configure('orm',[
            'directories' => [
                [
                    'namespace'     => $namespace,
                    'directory'     => $ormDir,
                    'map-namespace' => $namespace.'\\Ems'
                ]
            ]
        ]);

        $app->bind(SkeletonAppTest_ProjectRepository::class, SkeletonAppTest_OrmProjectRepository::class);
    }

    protected function boot(Application $app)
    {
        /** @var Router $router */
        $router = $app->get(Router::class);
        $router->register(function (RouteCollector $collector) {

            $collector->post('/project-types', SkeletonAppTest_ProjectController::class.'@storeType')
                ->entity(ProjectType::class, 'store')
                ->name('project-types.store');

            $collector->get('/project-types', SkeletonAppTest_ProjectController::class.'@listTypes')
                ->entity(ProjectType::class, 'index')
                ->name('project-types.index');

            $collector->get('/project-types/{type_id}', SkeletonAppTest_ProjectController::class.'@showType')
                ->entity(ProjectType::class, 'show')
                ->name('project-types.show');

            $collector->post('/projects', SkeletonAppTest_ProjectController::class.'@store')
                ->entity(Project::class, 'store')
                ->name('projects.store');

            $collector->get('/projects/{project_id}', SkeletonAppTest_ProjectController::class.'@show')
                ->entity(Project::class, 'show')
                ->name('projects.show');
        });

        /** @var ConnectionPool $connections */
        $connections = $app(ConnectionPool::class);
        $connections->extend('database://default', function () {
            return static::$con;
        });

    }


}

class SkeletonAppTest_ProjectController
{
    /**
     * @var SkeletonAppTest_ProjectRepository
     */
    protected $repository;
    /**
     * @var SchemaInspector
     */
    private $inspector;

    public function __construct(SkeletonAppTest_ProjectRepository $repository, SchemaInspector $inspector)
    {
        $this->repository = $repository;
        $this->inspector = $inspector;
    }

    public function store(Input $input, ResponseFactory $respond, UrlGenerator $urls)
    {
        $data = iterator_to_array($input);
        $typeId = $data['type']['id'];
        unset($data['type']);
        $data['type'] = $this->repository->getType($typeId);
        $user = new User();
        $user->id = $data['owner']['id'];
        $data['owner'] = $user;

        $project = $this->repository->create($data);
        $link = $urls->route('projects.show', [$project->id]);
        return $respond->create(json_encode($this->toArray($project)))
            ->withStatus(201)
            ->withEnvelope(['Link' => "<$link>; rel=\"self\""])
            ->withContentType('application/json');
    }

    public function storeType(Input $input, ResponseFactory $respond, UrlGenerator $urls)
    {
        $type = $this->repository->createType(iterator_to_array($input));
        $link = $urls->entity($type);
        return $respond->create(json_encode($this->toArray($type)))
            ->withStatus(201)
            ->withEnvelope(['Link' => "<$link>; rel=\"self\""])
            ->withContentType('application/json');
    }

    public function listTypes(ResponseFactory $respond)
    {
        $types = $this->repository->findTypes();
        return $respond->create(json_encode($this->listToArray($types)))->withContentType('application/json');
    }

    protected function listToArray($ormObjects) : array
    {
        $array = [];
        foreach ($ormObjects as $ormObject) {
            $array[] = $this->toArray($ormObject);
        }
        return $array;
    }

    protected function toArray($ormObject) : array
    {
        $keys = $this->inspector->getKeys(get_class($ormObject));
        $array = [];
        foreach ($keys as $key) {
            $value = $ormObject->$key;
            if (!is_object($value)) {
                $array[$key] = $value;
                continue;
            }
            if ($value instanceof DateTime) {
                $array[$key] = $value->format(DateTime::ATOM);
                continue;
            }
            if ($value instanceof Arrayable) {
                $array[$key] = $value->toArray();
            }

        }
        return $array;
    }
}

interface SkeletonAppTest_ProjectRepository
{
    /**
     * @param $id
     * @return Project
     */
    public function get($id) : Project;

    /**
     * @param array $attributes
     * @return Project
     */
    public function create(array $attributes) : Project;

    /**
     * @param array $attributes
     * @return ProjectType
     */
    public function createType(array $attributes) : ProjectType;

    /**
     * @return ProjectType[]
     */
    public function findTypes();

    /**
     * @param string|int $id
     * @return mixed
     */
    public function getType($id) : ProjectType;
}

class SkeletonAppTest_OrmProjectRepository implements SkeletonAppTest_ProjectRepository
{
    /**
     * @var Orm
     */
    protected $orm;

    public function __construct(Orm $orm)
    {
        $this->orm = $orm;
    }

    public function get($id): Project
    {
        // TODO: Implement get() method.
    }

    public function create(array $attributes): Project
    {
        if (isset($attributes['type'])) {
            $attributes['type_id'] = $attributes['type']->id;
            unset($attributes['type']);
        }
        if (isset($attributes['owner'])) {
            $attributes['owner_id'] = $attributes['owner']->id;
            unset($attributes['owner']);
        }
        /** @var Project $project */
        $project = $this->orm->query(Project::class)->create($attributes);
        return $project;
    }

    public function createType(array $attributes): ProjectType
    {
        /** @var ProjectType $type */
        $type = $this->orm->query(ProjectType::class)->create($attributes);
        return $type;
    }

    /**
     * @return \Ems\Model\OrmQuery|ProjectType[]
     */
    public function findTypes()
    {
        return $this->orm->query(ProjectType::class);
    }

    /**
     * @param string|int $id
     * @return ProjectType
     */
    public function getType($id): ProjectType
    {
        return $this->orm->query(ProjectType::class)
            ->where(ProjectTypeMap::ID, $id)->first();
    }

}