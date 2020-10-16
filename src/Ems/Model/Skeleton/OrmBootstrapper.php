<?php
/**
 *  * Created by mtils on 16.10.20 at 09:53.
 **/

namespace Ems\Model\Skeleton;


use Ems\Contracts\Core\Url;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Core\Application;
use Ems\Core\LocalFilesystem;
use Ems\Core\ObjectArrayConverter;
use Ems\Core\Skeleton\Bootstrapper;
use Ems\Model\Database\OrmQueryBuilder;
use Ems\Model\MapSchemaInspector;
use Ems\Model\Orm;

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

        $this->app->afterResolving(ObjectArrayConverter::class, function (ObjectArrayConverter $converter) {
            $inspector = $this->app->make(SchemaInspector::class);
            if (!$inspector instanceof MapSchemaInspector) {
                return;
            }
            $converter->setTypeProvider(function ($class, $path) use ($inspector) {
                return $inspector->type($class, $path);
            });
        });

        $this->app->resolving(Orm::class, function (Orm $orm) {
            $orm->extend('database', function (Url $url) {
                if ($url->scheme == 'database') {
                    return $this->app->make(OrmQueryBuilder::class);
                }
                return null;
            });
        });

        $app = $this->app->make(Application::class);

        if (!$mapDirectories = $app->config('orm.directories')) {
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
        $this->app->resolving(MapSchemaInspector::class, function (MapSchemaInspector $inspector) use ($mapDirectories) {
            $this->loadMaps($inspector, $mapDirectories);
        });
    }

    protected function loadMaps(MapSchemaInspector $inspector, array $mapDirectories)
    {
        $fs = new LocalFilesystem();
        foreach ($mapDirectories as $configuration) {

            $namespace = rtrim($configuration['namespace'], '\\');
            $mapNamespace = isset($configuration['map-namespace']) ? rtrim($configuration['map-namespace'], '\\') : $namespace.'\\'.'Maps';
            $mapSuffix = isset($configuration['map-suffix']) ? $configuration['map-suffix'] : 'Map';

            foreach ($fs->files($configuration['directory']) as $file) {

                $shortClass = $fs->name($file);
                $class = $namespace . '\\' .  $shortClass;
                $mapClass = $mapNamespace . '\\' . $shortClass . $mapSuffix;
                $inspector->map($class, $mapClass);
            }
        }
    }


}