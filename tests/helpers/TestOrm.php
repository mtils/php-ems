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
use Ems\Core\ObjectArrayConverter as ObjectFactory;
use Ems\Model\MapSchemaInspector;
use Ems\Model\StaticClassMap;
use Models\User;

use PHPUnit\Framework\Attributes\BeforeClass;

use function class_exists;

class MapStorage
{
    public static $mapClasses = [];
}

trait TestOrm
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

    protected function objectFactory(callable $typeProvider=null)
    {
        if (!$typeProvider) {
            $inspector = $this->newInspector();
            $typeProvider = function ($class, $path) use ($inspector) {
                return $inspector->type($class, $path);
            };
        }
        $converter = new ObjectFactory();
        $converter->setTypeProvider($typeProvider);
        return $converter;
    }

    protected function configureInspector(MapSchemaInspector $inspector)
    {
        foreach (MapStorage::$mapClasses as $class) {
            /** @var StaticClassMap $map */
            $map = new $class;
            $inspector->map($map->getOrmClass(), $map);
        }
    }

    /**
     * @noinspection PhpIncludeInspection
     */
    #[BeforeClass] public static function loadOrm()
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
            MapStorage::$mapClasses[] = $class;
            include_once($file);
        }
    }
}