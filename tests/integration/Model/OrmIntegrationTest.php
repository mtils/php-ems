<?php
/**
 *  * Created by mtils on 16.10.20 at 09:30.
 **/

namespace Ems\Model;


use Ems\Contracts\Core\ConnectionPool;
use Ems\Core\Application;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\LocalFilesystem;
use Ems\Core\Url;
use Ems\DatabaseIntegrationTest;
use Ems\Model\Skeleton\OrmBootstrapper;
use Ems\Pagination\Paginator;
use Ems\TestOrm;
use Models\Ems\UserMap;
use Models\Project;
use Models\ProjectType;
use Models\User;

use function class_exists;

class OrmIntegrationTest extends DatabaseIntegrationTest
{
    use TestOrm;

    protected $extraBootstrappers = [OrmBootstrapper::class];

    /**
     * @test
     */
    public function it_instantiates()
    {
        $this->assertInstanceOf(Orm::class, $this->app(Orm::class));
    }

    /**
     * @test
     */
    public function select_some_users_with_relations()
    {
        /** @var Orm $orm */
        $orm = $this->app(Orm::class);

        $query = $orm->query(User::class)
                    ->where(UserMap::EMAIL, 'like', 's%.com')
                    ->with('contact', 'projects.type');

        $users = [];

        /** @var User $user */
        foreach($query as $user) {
            $users[] = $user;
            $this->assertInstanceOf(User::class, $user);
            $this->assertStringStartsWith('s', $user->email);
            $this->assertNotEmpty($user->projects);
            $projectsFound = false;
            foreach ($user->projects as $project) {
                $this->assertInstanceOf(Project::class, $project);
                $this->assertEquals($user->id, $project->owner_id);
                $projectsFound = true;
                $this->assertInstanceOf(ProjectType::class, $project->type);
            }
            $this->assertTrue($projectsFound, 'No projects found');
        }
        $this->assertCount(19, $users);

        $query = $orm->query(User::class)
            ->where(UserMap::EMAIL, 'like', 's%.com')
            ->with('contact', 'projects.type');

        $result = $query->paginate();
        $users = [];

        /** @var User $user */
        foreach($result as $user) {
            $users[] = $user;
            $this->assertInstanceOf(User::class, $user);
            $this->assertStringStartsWith('s', $user->email);
            $this->assertNotEmpty($user->projects);
            $projectsFound = false;
            foreach ($user->projects as $project) {
                $this->assertInstanceOf(Project::class, $project);
                $this->assertEquals($user->id, $project->owner_id);
                $projectsFound = true;
                $this->assertInstanceOf(ProjectType::class, $project->type);
            }
            $this->assertTrue($projectsFound, 'No projects found');
        }
        $this->assertCount(15, $users);
        $this->assertInstanceOf(Paginator::class, $result);
        $this->assertEquals(19, $result->getTotalCount());

    }

    /**
     * @test
     */
    public function runner_for_unknown_url_throws_exception()
    {
        $this->expectException(HandlerNotFoundException::class);
        $this->app(Orm::class)->runner(new Url());
    }

    /**
     * Boot add the bootstrappers and boot the application.
     *
     * @param Application $app
     **/
    protected function bootApplication(Application $app)
    {
        static::manuallyLoadOrm();
        $ormDir = static::dirOfTests('database/orm');
        $namespace = "Models";

        $app->configure('orm.directories',[
            [
                'namespace'     => $namespace,
                'directory'     => $ormDir,
                'map-namespace' => $namespace.'\\Ems'
            ]
        ]);

        parent::bootApplication($app);

        /** @var ConnectionPool $connections */
        $connections = $app(ConnectionPool::class);
        $connections->extend('database://default', function () {
            return static::$con;
        });
    }

    /**
     * @beforeClass
     * @noinspection PhpIncludeInspection
     */
    public static function loadOrm()
    {

    }

    /**
     * @beforeClass
     * @noinspection PhpIncludeInspection
     */
    public static function manuallyLoadOrm()
    {
        if(class_exists(User::class)) {
            return;
        }

        $fs = new LocalFilesystem();

        $ormDir = static::dirOfTests('database/orm');
        $mapDir = "$ormDir/map";

        foreach($fs->files($ormDir) as $file) {
            include_once($file);
        }

        foreach($fs->files($mapDir) as $file) {
            include_once($file);
        }
    }
}