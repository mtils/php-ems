<?php
/**
 *  * Created by mtils on 25.07.20 at 07:38.
 **/

namespace integration\Model\Database;


use ArrayIterator;
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
use function is_array;
use function iterator_to_array;
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

        $hasGroup = function (array $groups, $name) {
            foreach ($groups as $groupData) {
                if ($groupData['name'] == $name) {
                    return true;
                }
            }
            return false;
        };

        foreach ($result as $user) {

            foreach (static::groupNames($user['email']) as $groupName) {
                if (!$hasGroup($user['groups'], $groupName)) {
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

        $hasGroup = function (array $groups, $name) {
            foreach ($groups as $groupData) {
                if ($groupData['name'] == $name) {
                    return true;
                }
            }
            return false;
        };

        //echo "\n" . SQL::render($dbResult->getDbQuery()->getAttached('to_many'));
        foreach ($result as $user) {

            print_r($user);

            foreach (static::groupNames($user['email']) as $groupName) {
                if (!$hasGroup($user['groups'], $groupName)) {
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
}