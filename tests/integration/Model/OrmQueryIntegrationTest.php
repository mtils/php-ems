<?php
/**
 *  * Created by mtils on 15.10.20 at 06:36.
 **/

namespace Ems\Model;


use Ems\Contracts\Pagination\Paginator;
use Models\Contact;
use Models\ProjectType;
use Models\User;
use Ems\Contracts\Core\Connection;
use Ems\Contracts\Core\ObjectArrayConverter;
use Ems\Contracts\Model\Inspector;
use Ems\Contracts\Model\OrmQueryRunner;
use Ems\DatabaseIntegrationTest;
use Ems\TestOrm;
use Models\Ems\UserMap;

use function print_r;
use function var_dump;

class OrmQueryIntegrationTest extends DatabaseIntegrationTest
{
    use TestOrm;

    /**
     * @test
     */
    public function it_instantiates()
    {
        $this->assertInstanceOf(OrmQuery::class, $this->make());
    }

    /**
     * @test
     */
    public function getters_returns_dependencies()
    {
        $query = $this->make();
        $this->assertInstanceOf(OrmQueryRunner::class, $query->getRunner());
        $this->assertInstanceOf(Connection::class, $query->getConnection());
        $this->assertInstanceOf(ObjectArrayConverter::class, $query->getObjectFactory());
    }

    /**
     * @test
     */
    public function select_some_users()
    {
        $query = $this->make()
            ->from(User::class)
            ->where(UserMap::EMAIL, 'like', 'b%');

        $users = [];

        /** @var User $user */
        foreach($query as $user) {
            $users[] = $user;
            $this->assertInstanceOf(User::class, $user);
            $this->assertStringStartsWith('b', $user->email);
        }
        $this->assertCount(16, $users);

    }

    /**
     * @test
     */
    public function select_some_users_with_relations()
    {
        $query = $this->make()
            ->with('contact', 'projects.type')
            ->from(User::class)
            ->where(UserMap::EMAIL, 'like', 's%.com');

        $users = [];

        /** @var User $user */
        foreach($query as $user) {
            $users[] = $user;
            $this->assertInstanceOf(User::class, $user);
            $this->assertStringStartsWith('s', $user->email);
            $this->assertNotEmpty($user->projects);
            $projectsFound = false;
            foreach ($user->projects as $project) {
                $this->assertEquals($user->id, $project->owner_id);
                $projectsFound = true;
                $this->assertInstanceOf(ProjectType::class, $project->type);
            }
            $this->assertTrue($projectsFound, 'No projects found');
        }
        $this->assertCount(19, $users);

    }

    /**
     * @test
     */
    public function paginate_some_users()
    {
        $query = $this->make()
            ->from(User::class)
            ->where(UserMap::WEB, 'not like', '%y%')
            ->orderBy('contact.first_name')
            ->orderBy('contact.last_name')
            ->with('contact');

        $result = $query->paginate(1, 15);
        $users = [];

        /** @var User $user */
        foreach($result as $user) {
            $users[] = $user;
            $this->assertInstanceOf(User::class, $user);
            $this->assertInstanceOf(Contact::class, $user->contact);
            $this->assertNotContains('y', $user->web);
        }
        $this->assertCount(15, $users);
        $this->assertEquals(359, $result->getTotalCount());

    }

    /**
     * @test
     */
    public function paginate_some_users_with_relations()
    {
        $query = $this->make()
            ->with('contact', 'projects.type')
            ->from(User::class)
            ->where(UserMap::EMAIL, 'like', 's%.com');

        $result = $query->paginate(1, 15);
        $users = [];

        /** @var User $user */
        foreach($result as $user) {
            $users[] = $user;
            $this->assertInstanceOf(User::class, $user);
            $this->assertStringStartsWith('s', $user->email);
            $this->assertNotEmpty($user->projects);
            $projectsFound = false;
            foreach ($user->projects as $project) {
                $this->assertEquals($user->id, $project->owner_id);
                $projectsFound = true;
                $this->assertInstanceOf(ProjectType::class, $project->type);
            }
            $this->assertTrue($projectsFound, 'No projects found');
        }
        $this->assertCount(15, $users);
        $this->assertEquals(19, $result->getTotalCount());

        $result = $query->paginate(2, 15);
        $users = [];

        /** @var User $user */
        foreach($result as $user) {
            $users[] = $user;
            $this->assertInstanceOf(User::class, $user);
            $this->assertStringStartsWith('s', $user->email);
            $this->assertNotEmpty($user->projects);
            $projectsFound = false;
            foreach ($user->projects as $project) {
                $this->assertEquals($user->id, $project->owner_id);
                $projectsFound = true;
                $this->assertInstanceOf(ProjectType::class, $project->type);
            }
            $this->assertTrue($projectsFound, 'No projects found');
        }
        $this->assertCount(4, $users);
        $this->assertEquals(19, $result->getTotalCount());

    }

    /**
     * @test
     */
    public function empty_result_getIterator_returns_empty_traversable()
    {
        $query = new OrmQuery();
        $rows = [];
        foreach ($query as $row) {
            $rows[] = $row;
        }
        $this->assertCount(0, $rows);
    }

    /**
     * @test
     */
    public function empty_result_paginate_returns_empty_paginator()
    {
        $query = new OrmQuery();
        $result = $query->paginate();
        $rows = [];
        foreach ($result as $row) {
            $rows[] = $row;
        }
        $this->assertInstanceOf(Paginator::class, $result);
        $this->assertCount(0, $result);
        $this->assertCount(0, $result->pages());
        $this->assertCount(0, $rows);
    }

    protected function make(OrmQueryRunner $runner=null, ObjectArrayConverter $factory=null, Inspector $inspector=null, Connection $connection=null)
    {
        $inspector = $inspector ?: $this->newInspector();
        $runner = $runner ?: $this->queryBuilder($inspector);
        $connection = $connection ?: static::$con;

        $typeProvider = function ($class, $path) use ($inspector) {
            return $inspector->type($class, $path);
        };

        $factory = $factory ?: $this->objectFactory($typeProvider);

        $query = new OrmQuery();
        $query->setRunner($runner)
            ->setConnection($connection)
            ->setObjectFactory($factory);

        return $query;
    }



}