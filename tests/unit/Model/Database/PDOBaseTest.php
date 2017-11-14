<?php


namespace Ems\Model\Database;

use Ems\Contracts\Model\Database\Connection;
use Ems\Core\Url;


abstract class PDOBaseTest extends \Ems\TestCase
{

    protected $testTable = 'CREATE TABLE `users` (
        `id`        INTEGER PRIMARY KEY AUTOINCREMENT,
        `login`     TEXT NOT NULL UNIQUE,
        `age`       INTEGER,
        `weight`    REAL
    );';

    protected function newConnection($createTable=true, Url $url=null)
    {
        $url = $url ?: new Url('sqlite://memory');
        $con = new PDOConnection($url);
        if ($createTable) {
            $this->createTable($con);
        }
        return $con;
    }

    protected function createTable(Connection $con)
    {
        $con->write($this->testTable);
    }
}
