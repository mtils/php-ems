<?php
/**
 *  * Created by mtils on 16.10.20 at 09:53.
 **/

namespace Ems\Model\Skeleton;


use Ems\Contracts\Core\Url;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Core\LocalFilesystem;
use Ems\Core\ObjectArrayConverter;
use Ems\Model\Database\OrmQueryBuilder;
use Ems\Model\MapSchemaInspector;
use Ems\Model\Orm;
use Ems\Skeleton\Bootstrapper;

use function rtrim;

class OrmBootstrapper extends Bootstrapper
{
    protected $singletons = [
        MapSchemaInspector::class    => SchemaInspector::class
    ];


    /**
     * Registers all in $this->bindings.
     **/
    protected function bindBindings()
    {
        parent::bindBindings();

        $this->container->onAfter(ObjectArrayConverter::class, function (ObjectArrayConverter $converter) {
            $inspector = $this->container->get(SchemaInspector::class);
            if (!$inspector instanceof MapSchemaInspector) {
                return;
            }
            $converter->setTypeProvider(function ($class, $path) use ($inspector) {
                return $inspector->type($class, $path);
            });
        });

        $this->container->on(Orm::class, function (Orm $orm) {
            $orm->extend('database', function (Url $url) {
                if ($url->scheme == 'database') {
                    return $this->app->get(OrmQueryBuilder::class);
                }
                return null;
            });
        });

        if (!$ormConfig = $this->app->config('orm')) {
            return;
        }

        if (!isset($ormConfig['directories'])) {
            return;
        }

        /**
         * [
         *   [
         *     "namespace"      => "App\Models",
         *     "directory"      => "%app/Models",
         *     "map-namespace"  => "App\Models\Maps",
         *     "map-suffix"     => "Map"
         *   ]
         * ]
         */
        $this->container->on(MapSchemaInspector::class, function (MapSchemaInspector $inspector) use ($ormConfig) {
            $this->loadMaps($inspector, $ormConfig['directories']);
        });
    }

    protected function loadMaps(MapSchemaInspector $inspector, array $mapDirectories)
    {
        $fs = new LocalFilesystem();
        foreach ($mapDirectories as $configuration) {

            $namespace = rtrim($configuration['namespace'], '\\');
            $mapNamespace = isset($configuration['map-namespace']) ? rtrim($configuration['map-namespace'], '\\') : $namespace.'\\'.'Maps';
            $mapSuffix = $configuration['map-suffix'] ?? 'Map';
            $directory = $configuration['directory'];
            $directory = $directory[0] == '/' ? $directory : $this->appPath() . '/' . $directory;

            foreach ($fs->files($directory) as $file) {
                $shortClass = $fs->name($file);
                $class = $namespace . '\\' .  $shortClass;
                $mapClass = $mapNamespace . '\\' . $shortClass . $mapSuffix;
                $inspector->map($class, $mapClass);
            }
        }
    }


}