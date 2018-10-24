<?php


namespace Ems\Testing\Eloquent;

use Ems\Events\Bus;
use Ems\Events\Laravel\EventDispatcher as LaravelDispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\SQLiteConnection;
use PDO;

/**
 * The InMemoryConnection uses a :memory: sqlite connection
 * to allow eloquent/database tests
 *
 * @property Application $app
 **/
trait InMemoryConnection
{

    /**
     * @var SQLiteConnection
     **/
    protected static $_testConnection;

    public function assertPreConditions()
    {
        $this->refreshConnectionIfNeeded();
    }

    /**
     * @beforeClass
     **/
    public function refreshConnectionIfNeeded()
    {
        if (!static::$_testConnection) {
            $this->refreshConnection();
        }
    }

    /**
     * Creates the connection and stores it
     **/
    protected function refreshConnection()
    {
        $this->createAndInjectConnection();
    }

    /**
     * Creates the connection and injects it into the application/model
     **/
    protected function createAndInjectConnection()
    {
        $connection = $this->createConnection($this->connectionName());
        $this->injectConnection($connection);
        static::$_testConnection = $connection;
    }

    /**
     * Creates an Illuminate SQLiteConnection
     *
     * @param string $name
     *
     * @return SQLiteConnection
     **/
    protected function createConnection($name)
    {

        $config = [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ];

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection = new SQLiteConnection($pdo, $name, '', $config);

        return $connection;
    }

    /**
     * Injects the connection into the laravel application (if running)
     *
     * @param ConnectionInterface $connection
     *
     **/
    protected function injectConnection(ConnectionInterface $connection)
    {

        if ($this->hasRunningApp()) {
            $this->injectAppConnection($connection);
        }

        $this->injectAppLessConnection($connection);

    }

    /**
     * Inject a connection into a running app
     *
     * @var ConnectionInterface $connection
     *
     **/
    protected function injectAppConnection(ConnectionInterface $connection)
    {

        $name = $this->connectionName();

        $config = [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ];

        $this->app['config']["database.connections.$name"] = $config;

        $this->app['config']['database.default'] = $name;

        $this->app['db']->extend($name, function() use ($connection){
            return $connection;
        });

        Model::getConnectionResolver()->setDefaultConnection($name);

    }

    /**
     * Inject a connection into Eloquent
     *
     * @var ConnectionInterface $connection
     *
     **/
    protected function injectAppLessConnection(ConnectionInterface $connection)
    {
        $resolver = new ConnectionResolver($connection);
        Model::setConnectionResolver($resolver);
        Model::setEventDispatcher(new LaravelDispatcher(new Bus));
    }

    /**
     * Return the connection name. Just define a connectionName
     * property to overwrite the default name "tests"
     *
     * @return string
     **/
    protected function connectionName()
    {
        if (property_exists($this, 'connectionName')) {
            return $this->connectionName;
        }
        return 'tests';
    }

    /**
     * Check if this is a laravel test with a running app
     *
     * @return bool
     **/
    protected function hasRunningApp()
    {
        if (!isset($this->app)) {
            return false;
        }

        return (bool)$this->app;
    }

    /**
     * A short debug method to dump a table into stdout.
     *
     * @param string $name
     * @param array $filters
     */
    protected function dumpTable($name, array $filters=[])
    {
        $query = static::$_testConnection->table($name);

        foreach ($filters as $key=>$value) {
            $query->where($key, $value);
        }

        foreach ($query->get() as $row) {
            print_r($row);
        }
    }

    /**
     * Truncate a table.
     *
     * @param string $table
     */
    protected function truncate($table)
    {
        static::$_testConnection->table($table)->truncate();
    }
}
