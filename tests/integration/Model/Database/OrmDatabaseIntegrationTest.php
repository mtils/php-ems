<?php
/**
 *  * Created by mtils on 25.07.20 at 07:38.
 **/

namespace integration\Model\Database;


use ArrayIterator;
use DateTime;
use Ems\Contracts\Model\OrmQuery;
use Ems\Core\Helper;
use Ems\DatabaseIntegrationTest;
use Ems\Model\ChunkIterator;
use Ems\Model\Database\DbOrmQueryResult;
use Ems\Model\Database\SQL;
use Ems\TestOrm;
use Models\Contact;
use Models\Ems\ContactMap;
use Models\Ems\TokenMap;
use Models\Ems\UserMap;
use Models\User;

use function array_key_exists;
use function crc32;
use function explode;
use function in_array;
use function is_array;
use function is_numeric;
use function iterator_to_array;
use function str_split;
use function var_dump;
use function var_export;

class OrmDatabaseIntegrationTest extends DatabaseIntegrationTest
{
    use TestOrm;

    /**
     * @test
     */
    public function select_contacts()
    {
        $query = new OrmQuery(Contact::class);
        $query->where(ContactMap::FIRST_NAME, 'like', 'Br%');

        $result = $this->queryBuilder()->retrieve(static::$con, $query);
        $this->assertEquals(75, $result->first()['id']);
        $this->assertEquals(400, $result->last()['id']);
        $iterator = $result->getIterator();
        $this->assertInstanceOf(ChunkIterator::class, $iterator);
        $this->assertCount(6, iterator_to_array($result));

    }

    /**
     * @test
     */
    public function select_contacts_unchunked()
    {
        $query = new OrmQuery(Contact::class);
        $query->where(ContactMap::FIRST_NAME, 'like', 'Br%');

        $result = $this->queryBuilder()->retrieve(static::$con, $query)->setChunkSize(0);
        $this->assertEquals(75, $result->first()['id']);
        $this->assertEquals(400, $result->last()['id']);
        $iterator = $result->getIterator();
        $this->assertInstanceOf(ArrayIterator::class, $iterator);
        $this->assertCount(6, iterator_to_array($result));

    }

    /**
     * @test
     */
    public function select_user_with_contact()
    {
        $query = (new OrmQuery(User::class))->with('contact');
        $query->where(UserMap::EMAIL, 'like', 'br%');

        $result = $this->queryBuilder()->retrieve(static::$con, $query);
        $this->assertEquals('Elkan', $result->first()['contact']['last_name']);
        $this->assertEquals('Capra', $result->last()['contact']['last_name']);
        $this->assertCount(4, iterator_to_array($result));

    }

    /**
     * @test
     */
    public function select_user_with_parent_and_contact()
    {
        $query = (new OrmQuery(User::class))->with('contact', 'parent');
        $query->where(UserMap::EMAIL, 'like', 's%');
        $query->where(UserMap::EMAIL, 'like', '%.com');

        $result = iterator_to_array($this->queryBuilder()->retrieve(static::$con, $query));

        foreach ($result as $user) {
            if ($user['id'] > 100) {
                $this->assertEquals($user['id']-100, $user['parent']['id'], "User {$user['id']} missing parent");
                continue;
            }
            if (!array_key_exists('parent', $user)) {
                $this->fail('The "parent" relation key must exist even if it does not exist: ' . var_export($user, true));
            }
            if (!is_array($user['parent'])) {
                $this->fail('Not existing relations must be an empty array not: ' . var_export($user['parent'], true));
            }
            if (array_key_exists('id', $user['parent'])) {
                $this->fail('Not existing relations must be an EMPTY array not: ' . var_export($user['parent'], true));
            }
        }
        $this->assertEquals('Cowser', $result[0]['contact']['last_name']);
        $this->assertEquals('Gaucher', $result[18]['contact']['last_name']);
        $this->assertEquals('eringlein@gmail.com', $result[18]['parent']['email']);
        $this->assertCount(19, $result);

    }

    /**
     * @test
     */
    public function select_user_with_parent_and_contact_of_parent()
    {
        $query = (new OrmQuery(User::class))->with('contact', 'parent.contact');
        $query->where(UserMap::EMAIL, 'like', 's%');
        $query->where(UserMap::EMAIL, 'like', '%.com');

        $dbResult = $this->queryBuilder()->retrieve(static::$con, $query);
        $result = iterator_to_array($dbResult);

        foreach ($result as $user) {
            if ($user['id'] > 100) {
                $this->assertEquals($user['id']-100, $user['parent']['id'], "User {$user['id']} missing parent");
                continue;
            }
            if (!array_key_exists('parent', $user)) {
                $this->fail('The "parent" relation key must exist even if it does not exist: ' . var_export($user, true));
            }
            if (!is_array($user['parent'])) {
                $this->fail('Not existing relations must be an empty array not: ' . var_export($user['parent'], true));
            }
            if (array_key_exists('id', $user['parent'])) {
                $this->fail('Not existing relations must be an EMPTY array not: ' . var_export($user['parent'], true));
            }
        }

        $this->assertEquals('Cowser', $result[0]['contact']['last_name']);
        $this->assertEquals('Gaucher', $result[18]['contact']['last_name']);
        $this->assertEquals('eringlein@gmail.com', $result[18]['parent']['email']);
        $this->assertEquals('Ringlein', $result[18]['parent']['contact']['last_name']);
        $this->assertCount(19, $result);

    }

    /**
     * @test
     */
    public function select_user_with_to_many_tokens()
    {
        $query = (new OrmQuery(User::class))->with('tokens', 'contact');
        $query->where(UserMap::EMAIL, 'like', 's%');
        $query->where(UserMap::EMAIL, 'like', '%.com');

        $dbResult = $this->queryBuilder()->retrieve(static::$con, $query);
        $result = iterator_to_array($dbResult);

        foreach ($result as $user) {
            $lastDigit = (int)Helper::last($user['contact']['phone1']);
            $count = $lastDigit == 0 ? 2 : $lastDigit;
            $this->assertCount($count, $user['tokens']);
            foreach($user['tokens'] as $tokenArray) {
                $token = crc32($user['email'] . '-' . $tokenArray[TokenMap::TOKEN_TYPE]);
                $this->assertEquals($token, $tokenArray['token']);
            }
        }

    }

    /**
     * @test
     */
    public function select_user_with_m_to_n_groups()
    {
        $query = (new OrmQuery(User::class))->with('groups', 'contact');
        $query->where('contact.last_name', 'like', 's%');
        $query->where('contact.city', 'like', '% %');

        /** @var DbOrmQueryResult $dbResult */
        $dbResult = $this->queryBuilder()->retrieve(static::$con, $query);

        $result = iterator_to_array($dbResult);

        foreach ($result as $user) {

            foreach (static::groupNames($user['email']) as $groupName) {
                if (!$this->hasItem($user['groups'], $groupName)) {
                    $this->fail("User is missing group $groupName");
                }
            }

        }
    }

    /**
     * @test
     */
    public function select_user_with_groups_and_token()
    {
        $query = (new OrmQuery(User::class))->with('groups', 'contact', 'tokens');
        $query->where(UserMap::EMAIL, 'like', 's%');
        $query->where(UserMap::EMAIL, 'like', '%.com');

        /** @var DbOrmQueryResult $dbResult */
        $dbResult = $this->queryBuilder()->retrieve(static::$con, $query);

        $result = iterator_to_array($dbResult);

        foreach ($result as $user) {

            foreach (static::groupNames($user['email']) as $groupName) {
                if (!$this->hasItem($user['groups'], $groupName)) {
                    $this->fail("User is missing group $groupName");
                }
            }

            $lastDigit = (int)Helper::last($user['contact']['phone1']);
            $count = $lastDigit == 0 ? 2 : $lastDigit;
            $this->assertCount($count, $user['tokens']);
            foreach($user['tokens'] as $tokenArray) {
                $token = crc32($user['email'] . '-' . $tokenArray[TokenMap::TOKEN_TYPE]);
                $this->assertEquals($token, $tokenArray['token']);
            }

        }
    }

    /**
     * @test
     */
    public function select_user_with_m_to_n_to_one_projects_type()
    {
        $query = (new OrmQuery(User::class))->with('projects.type', 'contact');
        $query->where(UserMap::EMAIL, 'like', 's%');

        /** @var DbOrmQueryResult $dbResult */
        $dbResult = $this->queryBuilder()->retrieve(static::$con, $query);

        $result = iterator_to_array($dbResult);

        foreach ($result as $user) {

            $countyWords = explode(' ', $user['contact']['county']);

            $this->assertCount(count($countyWords), $user['projects']);

            $mailProvider = static::mailProvider($user['email']);

            foreach($countyWords as $countyWord) {
                $this->assertTrue($this->hasItem($user['projects'], $countyWord));
            }

            foreach($user['projects'] as $project) {
                $this->assertEquals($mailProvider, $project['type']['name']);
            }

        }
    }

    /**
     * @test
     */
    public function select_user_with_m_to_n_to_many_projects_files()
    {
        $query = (new OrmQuery(User::class))->with('projects.files', 'contact');
        $query->where(UserMap::EMAIL, 'like', 's%');

        /** @var DbOrmQueryResult $dbResult */
        $dbResult = $this->queryBuilder()->retrieve(static::$con, $query);
        $result = iterator_to_array($dbResult);

        foreach ($result as $user) {
            $countyWords = explode(' ', $user['contact']['county']);

            $this->assertCount(count($countyWords), $user['projects']);

            foreach($countyWords as $countyWord) {
                $this->assertTrue($this->hasItem($user['projects'], $countyWord));
            }

            foreach($user['projects'] as $project) {
                $digits = str_split(explode(' ', $user['contact']['address'])[0]);
                $this->assertCount(count($digits), $project['files']);
                foreach ($digits as $i=>$digit) {
                    $name = 'project_file_' . $project['id'] . "_$digit-$i.jpg";
                    $this->assertTrue($this->hasItem($project['files'], $name));
                }
            }

        }
    }

    /**
     * @test
     */
    public function select_user_with_many_different_relations()
    {
        $query = (new OrmQuery(User::class))->with('projects.files', 'projects.type', 'tokens', 'groups', 'contact');
        $query->where(UserMap::EMAIL, 'like', 's%');

        /** @var DbOrmQueryResult $dbResult */
        $dbResult = $this->queryBuilder()->retrieve(static::$con, $query);
        $result = iterator_to_array($dbResult);

        foreach ($result as $user) {

            $countyWords = explode(' ', $user['contact']['county']);

            $this->assertCount(count($countyWords), $user['projects']);

            foreach($countyWords as $countyWord) {
                $this->assertTrue($this->hasItem($user['projects'], $countyWord));
            }

            $mailProvider = static::mailProvider($user['email']);

            foreach($user['projects'] as $project) {
                $this->assertEquals($mailProvider, $project['type']['name']);
            }

            foreach($user['projects'] as $project) {
                $digits = str_split(explode(' ', $user['contact']['address'])[0]);
                $this->assertCount(count($digits), $project['files']);
                foreach ($digits as $i=>$digit) {
                    $name = 'project_file_' . $project['id'] . "_$digit-$i.jpg";
                    $this->assertTrue($this->hasItem($project['files'], $name));
                }
            }

            foreach (static::groupNames($user['email']) as $groupName) {
                if (!$this->hasItem($user['groups'], $groupName)) {
                    $this->fail("User is missing group $groupName");
                }
            }

            $lastDigit = (int)Helper::last($user['contact']['phone1']);
            $count = $lastDigit == 0 ? 2 : $lastDigit;
            $this->assertCount($count, $user['tokens']);
            foreach($user['tokens'] as $tokenArray) {
                $token = crc32($user['email'] . '-' . $tokenArray[TokenMap::TOKEN_TYPE]);
                $this->assertEquals($token, $tokenArray['token']);
            }

        }
    }

    /**
     * @test
     */
    public function select_to_one_that_has_to_many_contact_user()
    {
        $query = (new OrmQuery(Contact::class))->with('user.tokens');
        $query->where('user.email', 'like', 's%');

        $result = $this->queryBuilder()->retrieve(static::$con, $query);
        foreach ($result as $contact) {

            $lastDigit = (int)Helper::last($contact['phone1']);
            $count = $lastDigit == 0 ? 2 : $lastDigit;
            $this->assertCount($count, $contact['user']['tokens']);
            foreach($contact['user']['tokens'] as $tokenArray) {
                $token = crc32($contact['user']['email'] . '-' . $tokenArray[TokenMap::TOKEN_TYPE]);
                $this->assertEquals($token, $tokenArray['token']);
            }

        }

    }

    /**
     * @test
     */
    public function select_to_one_that_has_to_many_that_has_to_many_and_to_one_contact_user_project()
    {
        $query = (new OrmQuery(Contact::class))->with('user.tokens', 'user.projects.files', 'user.projects.type');
        $query->where('user.email', 'like', 's%');

        /** @var DbOrmQueryResult $result */
        $result = $this->queryBuilder()->retrieve(static::$con, $query);

        foreach ($result as $contact) {

            $lastDigit = (int)Helper::last($contact['phone1']);
            $count = $lastDigit == 0 ? 2 : $lastDigit;
            $this->assertCount($count, $contact['user']['tokens']);
            foreach($contact['user']['tokens'] as $tokenArray) {
                $token = crc32($contact['user']['email'] . '-' . $tokenArray[TokenMap::TOKEN_TYPE]);
                $this->assertEquals($token, $tokenArray['token']);
            }

            $countyWords = explode(' ', $contact['county']);

            $this->assertCount(count($countyWords), $contact['user']['projects']);

            foreach($countyWords as $countyWord) {
                $this->assertTrue($this->hasItem($contact['user']['projects'], $countyWord));
            }

            $mailProvider = static::mailProvider($contact['user']['email']);

            foreach($contact['user']['projects'] as $project) {
                $this->assertEquals($mailProvider, $project['type']['name']);
            }

            foreach($contact['user']['projects'] as $project) {
                $digits = str_split(explode(' ', $contact['address'])[0]);
                $this->assertCount(count($digits), $project['files']);
                foreach ($digits as $i=>$digit) {
                    $name = 'project_file_' . $project['id'] . "_$digit-$i.jpg";
                    $this->assertTrue($this->hasItem($project['files'], $name));
                }
            }
        }

    }

    /**
     * @test
     */
    public function select_user_with_many_different_relations_or_none()
    {
        $query = (new OrmQuery(User::class))->with('projects.files', 'projects.type', 'tokens', 'groups', 'contact');
        $query('or')->where(UserMap::EMAIL, 'like', 'st%')
        ->where(UserMap::EMAIL, 'like', 'u%');

        /** @var DbOrmQueryResult $dbResult */
        $dbResult = $this->queryBuilder()->retrieve(static::$con, $query);
        $result = iterator_to_array($dbResult);

        foreach ($result as $user) {

            if ($user['email'][0] != 's') {
                $this->assertSame([],$user['projects']);
                $this->assertSame([],$user['tokens']);
                continue;
            }

            $countyWords = explode(' ', $user['contact']['county']);

            $this->assertCount(count($countyWords), $user['projects']);

            foreach($countyWords as $countyWord) {
                $this->assertTrue($this->hasItem($user['projects'], $countyWord));
            }

            $mailProvider = static::mailProvider($user['email']);

            foreach($user['projects'] as $project) {
                $this->assertEquals($mailProvider, $project['type']['name']);
            }

            foreach($user['projects'] as $project) {
                $digits = str_split(explode(' ', $user['contact']['address'])[0]);
                $this->assertCount(count($digits), $project['files']);
                foreach ($digits as $i=>$digit) {
                    $name = 'project_file_' . $project['id'] . "_$digit-$i.jpg";
                    $this->assertTrue($this->hasItem($project['files'], $name));
                }
            }

            foreach (static::groupNames($user['email']) as $groupName) {
                if (!$this->hasItem($user['groups'], $groupName)) {
                    $this->fail("User is missing group $groupName");
                }
            }

            $lastDigit = (int)Helper::last($user['contact']['phone1']);
            $count = $lastDigit == 0 ? 2 : $lastDigit;
            $this->assertCount($count, $user['tokens']);
            foreach($user['tokens'] as $tokenArray) {
                $token = crc32($user['email'] . '-' . $tokenArray[TokenMap::TOKEN_TYPE]);
                $this->assertEquals($token, $tokenArray['token']);
            }

        }
    }

    /**
     * @test
     */
    public function create_user()
    {
        $data = [
            UserMap::EMAIL      => 'test@test.de',
            UserMap::PASSWORD   => '123',
            UserMap::WEB        => 'https://www.test.de',
            UserMap::CREATED_AT => new DateTime(),
            UserMap::UPDATED_AT => new DateTime(),
        ];
        $queryBuilder = $this->queryBuilder();
        $insertedId = $queryBuilder->create(static::$con, User::class, $data);
        $this->assertTrue(is_numeric($insertedId) && (int)$insertedId > 500);

        $query = $queryBuilder->query(User::class)->where('id', $insertedId);
        $result = $queryBuilder->retrieve(static::$con, $query)->first();

        foreach($data as $key=>$value) {
            if (in_array($key, [UserMap::CREATED_AT, UserMap::UPDATED_AT])) {
                continue;
            }
            $this->assertEquals($value, $result[$key]);
        }

    }

    /**
     * @test
     */
    public function update_user()
    {
        $data = [
            UserMap::EMAIL      => 'test2@test.de',
            UserMap::PASSWORD   => '123',
            UserMap::WEB        => 'https://www.test.de',
            UserMap::CREATED_AT => new DateTime(),
            UserMap::UPDATED_AT => new DateTime(),
        ];
        $queryBuilder = $this->queryBuilder();
        $insertedId = $queryBuilder->create(static::$con, User::class, $data);

        $query = $queryBuilder->query(User::class)->where('id', $insertedId);

        $updates = [
            UserMap::PASSWORD => '456',
            UserMap::WEB      => 'https://www.test2.de'
        ];
        $this->assertSame(1, $queryBuilder->update(static::$con, $query, $updates));

        $result = $queryBuilder->retrieve(static::$con, $query)->first();

        foreach ($updates as $key=>$update) {
            $this->assertEquals($update, $result[$key]);
        }
    }

    /**
     * @test
     */
    public function delete_user()
    {
        $data = [
            UserMap::EMAIL      => 'test3@test.de',
            UserMap::PASSWORD   => '123',
            UserMap::WEB        => 'https://www.test.de',
            UserMap::CREATED_AT => new DateTime(),
            UserMap::UPDATED_AT => new DateTime(),
        ];
        $queryBuilder = $this->queryBuilder();
        $insertedId = $queryBuilder->create(static::$con, User::class, $data);
        $this->assertTrue(is_numeric($insertedId) && (int)$insertedId > 500);

        $query = $queryBuilder->query(User::class)->where('id', $insertedId);
        $result = $queryBuilder->retrieve(static::$con, $query)->first();

        foreach($data as $key=>$value) {
            if (in_array($key, [UserMap::CREATED_AT, UserMap::UPDATED_AT])) {
                continue;
            }
            $this->assertEquals($value, $result[$key]);
        }

        $this->assertSame(1, $queryBuilder->delete(static::$con, $query));

        $this->assertNull($queryBuilder->retrieve(static::$con, $query)->first());
    }

    /**
     * @test
     */
    public function paginate_users_with_m_to_n_groups()
    {
        $query = (new OrmQuery(User::class))->with('groups', 'contact');
        $query->where('contact.last_name', 'like', 's%');
        $query->where('contact.city', 'like', '% %');

        /** @var DbOrmQueryResult $dbResult */
        $dbResult = $this->queryBuilder()->retrieve(static::$con, $query);

        $perPage = 15;

        $paginator = $dbResult->paginate(1,$perPage);

        $this->assertSame(48, $paginator->getTotalCount());

        $this->assertCount($perPage, $paginator);

        foreach ($paginator as $user) {

            foreach (static::groupNames($user['email']) as $groupName) {
                if (!$this->hasItem($user['groups'], $groupName)) {
                    $this->fail("User is missing group $groupName");
                }
            }

        }

        $paginator2 = $dbResult->paginate(2,$perPage);

        $this->assertSame(48, $paginator2->getTotalCount());
        $this->assertCount($perPage, $paginator2);
        $this->assertNotSame($paginator, $paginator2);

        $paginator3 = $dbResult->paginate(4, $perPage);

        $this->assertSame(48, $paginator3->getTotalCount());
        $this->assertCount(3, $paginator3);


    }

    private function hasItem(array $items, $name, $property='name')
    {
        foreach ($items as $itemData) {
            if ($itemData['name'] == $name) {
                return true;
            }
        }
        return false;
    }
}