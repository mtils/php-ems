<?php
/**
 *  * Created by mtils on 15.10.20 at 06:36.
 **/

namespace Ems\Model;


use DateTime;
use Ems\Contracts\Core\Connection;
use Ems\Contracts\Core\ObjectArrayConverter;
use Ems\Contracts\Model\Inspector;
use Ems\Contracts\Model\OrmQueryRunner;
use Ems\Contracts\Pagination\Paginator;
use Ems\DatabaseIntegrationTest;
use Ems\TestOrm;
use Models\Contact;
use Models\Ems\UserMap;
use Models\Ems\ContactMap;
use Models\ProjectType;
use Models\User;

use PHPUnit\Framework\Attributes\Test;

use function print_r;

class OrmQueryIntegrationTest extends DatabaseIntegrationTest
{
    use TestOrm;

    #[Test] public function it_instantiates()
    {
        $this->assertInstanceOf(OrmQuery::class, $this->make());
    }

    #[Test] public function getters_returns_dependencies()
    {
        $query = $this->make();
        $this->assertInstanceOf(OrmQueryRunner::class, $query->getRunner());
        $this->assertInstanceOf(Connection::class, $query->getConnection());
        $this->assertInstanceOf(ObjectArrayConverter::class, $query->getObjectFactory());
    }

    #[Test] public function select_some_users()
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

    #[Test] public function select_some_users_with_relations()
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

    #[Test] public function paginate_some_users()
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
            $this->assertStringNotContainsString('y', $user->web);
        }
        $this->assertCount(15, $users);
        $this->assertEquals(359, $result->getTotalCount());

    }

    #[Test] public function paginate_some_users_with_relations()
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

    #[Test] public function empty_result_getIterator_returns_empty_traversable()
    {
        $query = new OrmQuery();
        $rows = [];
        foreach ($query as $row) {
            $rows[] = $row;
        }
        $this->assertCount(0, $rows);
    }

    #[Test] public function empty_result_paginate_returns_empty_paginator()
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

    /***************************************************************************
     * Hier weiter:
     * -------------------------------------------------------------------------
     * insert, update, delete. Muss mit auto attributes, defaults und casting
     * laufen
     **************************************************************************/
    #[Test] public function create_contact()
    {
        $query = $this->make()->from(Contact::class);

        $data = [
            ContactMap::FIRST_NAME  => 'Michael',
            ContactMap::LAST_NAME   => 'Tils',
            ContactMap::ADDRESS     => 'His home 1',
            ContactMap::CITY        => 'Old York',
            ContactMap::POSTAL      => '123456',
            ContactMap::PHONE1      => '+49 71145 548451'
        ];

        /** @var Contact $contact */
        $contact = $query->create($data);

        $this->assertInstanceOf(Contact::class, $contact);
        foreach ($data as $key=>$value) {
            $this->assertEquals($value, $contact->{$key});
        }
        $this->assertGreaterThan(0, $contact->id);
        $this->assertIsInt($contact->id);
        $this->assertInstanceOf(DateTime::class, $contact->created_at);
        $this->assertInstanceOf(DateTime::class, $contact->updated_at);

        $storedContact = $this->make()->from(Contact::class)
            ->where('id', $contact->id)->first();

        $this->assertEquals($contact->toArray(), $storedContact->toArray());

    }

    #[Test] public function save_contact()
    {
        $query = $this->make();

        $data = [
            ContactMap::FIRST_NAME  => 'Michael',
            ContactMap::LAST_NAME   => 'Tils',
            ContactMap::ADDRESS     => 'His home 1',
            ContactMap::CITY        => 'Old York',
            ContactMap::POSTAL      => '123456',
            ContactMap::PHONE1      => '+49 71145 548451'
        ];

        /** @var Contact $contact */
        $contact = $query->from(Contact::class)->create($data);

        $this->assertEquals($data[ContactMap::FIRST_NAME], $contact->first_name);

        $contact->first_name = 'Olaf';
        $updates = $query->from(Contact::class)->save($contact);
        $this->assertTrue(count($updates) > 1);

        /** @var Contact $dbContact */
        $dbContact = $this->make()->from(Contact::class)
            ->where(ContactMap::ID, $contact->id)->first();
        $this->assertEquals($contact->first_name, $dbContact->first_name);
    }

    #[Test] public function create_and_delete_contact()
    {
        $query = $this->make()->from(Contact::class);

        $data = [
            ContactMap::FIRST_NAME  => 'Michael',
            ContactMap::LAST_NAME   => 'Tils',
            ContactMap::ADDRESS     => 'His home 1',
            ContactMap::CITY        => 'Old York',
            ContactMap::POSTAL      => '123456',
            ContactMap::PHONE1      => '+49 71145 548451'
        ];

        /** @var Contact $contact */
        $contact = $query->create($data);

        $this->assertInstanceOf(Contact::class, $contact);
        foreach ($data as $key=>$value) {
            $this->assertEquals($value, $contact->{$key});
        }
        $this->assertGreaterThan(0, $contact->id);
        $this->assertIsInt($contact->id);
        $this->assertInstanceOf(DateTime::class, $contact->created_at);
        $this->assertInstanceOf(DateTime::class, $contact->updated_at);

        $storedContact = $this->make()->from(Contact::class)
            ->where('id', $contact->id)->first();

        $this->assertEquals($contact->toArray(), $storedContact->toArray());

        $this->assertEquals(1, $this->make()->from(Contact::class)->delete($contact));

        $this->assertNull($this->make()->from(Contact::class)
            ->where('id', $contact->id)->first());

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
            ->setObjectFactory($factory)
            ->setSchemaInspector($inspector);

        return $query;
    }



}