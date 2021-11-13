<?php
/**
 *  * Created by mtils on 07.11.2021 at 13:34.
 **/

namespace Ems\Model\Schema\Illuminate;

use Closure;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Model\Exceptions\MigrationClassNotFoundException;
use Ems\Contracts\Model\Schema\MigrationRunner;
use Ems\Core\Lambda;
use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Support\CustomFactorySupport;
use Ems\Model\Database\SQL;
use Ems\Model\Eloquent\EmsConnectionFactory;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Grammars\Grammar;
use ReflectionException;
use Throwable;

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

    public function __construct(EmsConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

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

    /**
     * Load the migration class and call its method.
     *
     * @param string $file
     * @param string $method
     * @param bool   $simulate
     *
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function runMigration(string $file, string $method, bool $simulate=false)
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

    /**
     * Make a closure to call the migration method (up or down)
     *
     * @param Connection       $connection
     * @param Migration|object $migration
     * @param string $method
     *
     * @return Closure
     */
    protected function makeClosure(Connection $connection, $migration, string $method) : Closure
    {
        return function () use ($migration, $method, $connection) {
            $reflection = Lambda::reflect([$migration, $method]);
            $customInjections = [];
            foreach($reflection as $name=>$info) {
                if ($info['type'] == Builder::class) {
                    $customInjections[Builder::class] = $connection->getSchemaBuilder();
                }
            }
            $args = Lambda::buildHintedArguments($reflection, $this->_customFactory, $customInjections);
            return Lambda::callNamed([$migration, $method], $args);
        };
    }

    /**
     * @param string $file
     * @return object
     * @throws ReflectionException
     */
    protected function resolveClass(string $file)
    {
        if (!$class = Type::classInFile($file)) {
            throw new MigrationClassNotFoundException("No class found in migration '$file'");
        }
        if ($class == Type::ANONYMOUS_CLASS) {
            return require($file);
        }

        require_once $file;
        return $this->createObject($class);
    }

    /**
     * Resolve the connection of $migration.
     *
     * @param object|Migration $migration
     *
     * @return Connection
     */
    protected function resolveConnection($migration) : Connection
    {
        $connectionName = $this->connectionFactory->getDefaultConnection();
        if ($migration instanceof Migration) {
            $connectionName = $migration->getConnection();
        }

        $connection = $this->connectionFactory->connection($connectionName);
        $this->listenToConnection($connection, QueryExecuted::class);
        return $connection;
    }

    /**
     * Forward the connection events to hooks.
     *
     * @param Connection $connection
     * @param string $eventClass
     */
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