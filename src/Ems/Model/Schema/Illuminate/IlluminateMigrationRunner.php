<?php
/**
 *  * Created by mtils on 07.11.2021 at 13:34.
 **/

namespace Ems\Model\Schema\Illuminate;

use Closure;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Model\Schema\MigrationRunner;
use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Support\CustomFactorySupport;
use Ems\Model\Database\SQL;
use Ems\Model\Eloquent\EmsConnectionFactory;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Grammars\Grammar;

use function call_user_func;
use function spl_object_hash;

class IlluminateMigrationRunner implements MigrationRunner, SupportsCustomFactory, HasMethodHooks
{
    use CustomFactorySupport;
    use HookableTrait;

    /**
     * @var EmsConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @var string[]
     */
    protected $listenedConnectionHashes = [];

    /**
     * @param string $file
     * @param bool $simulate
     */
    public function upgrade(string $file, bool $simulate = false)
    {
        $this->runMigration($file, 'up', $simulate);
    }

    /**
     * @param string $file
     * @param bool $simulate
     */
    public function downgrade(string $file, bool $simulate = false)
    {
        $this->runMigration($file, 'down', $simulate);
    }

    /**
     * @return string[]
     */
    public function methodHooks()
    {
        return ['query'];
    }

    protected function runMigration(string $file, $method, bool $simulate=false)
    {
        $migration = $this->resolveClass($file);
        $connection = $this->resolveConnection($migration);
        $callback = $this->makeClosure($connection, $migration, $method);

        if ($simulate) {
            $connection->pretend($callback);
            return;
        }

        if ($this->getSchemaGrammar($connection)->supportsSchemaTransactions()) {
            $connection->transaction($callback);
            return;
        }

        $callback();
    }

    protected function makeClosure(Connection $connection, $migration, string $method) : Closure
    {
        return function () use ($migration, $method, $connection) {
            $args = [];
            if ($migration instanceof IlluminateMigration) {
                $args[] = $connection->getSchemaBuilder();
            }
            return call_user_func([$migration, $method], ...$args);
        };
    }

    /**
     * @param string $file
     * @return object
     * @throws \ReflectionException
     */
    protected function resolveClass(string $file)
    {
        if (!$class = Type::classInFile($file)) {
            //throw new ClassNotFoundException
        }
        if ($class = Type::ANONYMOUS_CLASS) {
            return require($file);
        }

        return $this->createObject($class);
    }

    protected function resolveConnection($migration) : Connection
    {
        $connectionName = $this->connectionFactory->getDefaultConnection();
        if ($migration instanceof Migration) {
            $connectionName = $migration->getConnection();
        }
        $connection = $this->connectionFactory->connection($connectionName);
        $this->listenToConnection($connection);
        return $connection;
    }

    protected function listenToConnection(Connection $connection, string $eventClass)
    {
        $hash = spl_object_hash($connection);
        if (isset($this->listenedConnectionHashes[$hash][$eventClass])) {
            return;
        }
        if (!isset($this->listenedConnectionHashes[$hash])) {
            $this->listenedConnectionHashes[$hash] = [];
        }
        $this->listenedConnectionHashes[$hash][$eventClass] = true;

        if ($eventClass == QueryExecuted::class) {
            $connection->listen(function (QueryExecuted $event) {
                $sql = $event->bindings ? SQL::render(
                    $event->sql,
                    $event->bindings
                ) : $event->sql;
                $this->callBeforeListeners('query', [$sql]);
                $this->callAfterListeners('query', [$sql]);
            });
        }
    }

    /**
     * @param Connection $connection
     * @return Grammar
     */
    protected function getSchemaGrammar(Connection $connection) : Grammar
    {
        if (!$connection->getSchemaGrammar()) {
            $connection->useDefaultSchemaGrammar();
        }
        return $connection->getSchemaGrammar();
    }
}