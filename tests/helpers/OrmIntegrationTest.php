<?php
/**
 *  * Created by mtils on 01.06.20 at 09:29.
 **/

namespace Ems;


use Ems\Contracts\Model\OrmQueryRunner;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Core\LocalFilesystem;
use Ems\Model\Database\DbOrmQueryRunner;
use Ems\Model\Database\OrmQueryBuilder;
use Ems\Model\MapSchemaInspector;
use Ems\Model\StaticClassMap;
use Models\User;

use function class_exists;

class OrmIntegrationTest extends DatabaseIntegrationTest
{
    /**
     * @var string[]
     */
    protected static $mapClasses = [];

    protected function newInspector($configure=true)
    {
        $inspector = new MapSchemaInspector();
        if($configure) {
            $this->configureInspector($inspector);
        }
        return $inspector;
    }

    protected function queryBuilder(SchemaInspector $inspector=null)
    {
        return new OrmQueryBuilder($inspector ?: $this->newInspector());
    }

    protected function queryRunner(SchemaInspector $inspector=null, OrmQueryBuilder $queryBuilder=null)
    {
        $inspector = $inspector ?: $this->newInspector();
        $queryBuilder = $queryBuilder ?: $this->queryBuilder($inspector);
        return new DbOrmQueryRunner($inspector, $queryBuilder);
    }

    protected function configureInspector(MapSchemaInspector $inspector)
    {
        foreach (static::$mapClasses as $class) {
            /** @var StaticClassMap $map */
            $map = new $class;
            $inspector->map($map->getOrmClass(), $map);
        }
    }

    /**
     * @beforeClass
     * @noinspection PhpIncludeInspection
     */
    public static function loadOrm()
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
            $class = "Models\\Ems\\" . $fs->name($file);
            static::$mapClasses[] = $class;
            include_once($file);
        }
    }
}