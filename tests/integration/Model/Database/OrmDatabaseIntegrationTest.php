<?php
/**
 *  * Created by mtils on 25.07.20 at 07:38.
 **/

namespace integration\Model\Database;


use ArrayIterator;
use Ems\Contracts\Model\OrmQuery;
use Ems\DatabaseIntegrationTest;
use Ems\Model\ChunkIterator;
use Ems\TestOrm;
use Models\Contact;
use Models\Ems\ContactMap;

use Models\Ems\UserMap;
use Models\User;

use function get_class;
use function iterator_to_array;

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
}