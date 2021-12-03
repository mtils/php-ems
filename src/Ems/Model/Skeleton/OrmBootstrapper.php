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

use const APP_ROOT;

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

        $this->app->onAfter(ObjectArrayConverter::class, function (ObjectArrayConverter $converter) {
            $inspector = $this->app->get(SchemaInspector::class);
            if (!$inspector instanceof MapSchemaInspector) {
                return;
            }
            $converter->setTypeProvider(function ($class, $path) use ($inspector) {
                return $inspector->type($class, $path);
            });
        });

        $this->app->on(Orm::class, function (Orm $orm) {
            $orm->extend('database', function (Url $url) {
                if ($url->scheme == 'database') {
                    return $this->app->get(OrmQueryBuilder::class);
                }
                return null;
            });
        });

        /** @var Application $app */
        $app = $this->app->get(Application::class);

        if (!$ormConfig = $app->config('orm')) {
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
        $this->app->on(MapSchemaInspector::class, function (MapSchemaInspector $inspector) use ($ormConfig) {
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
            $directory = $directory[0] == '/' ? $directory : APP_ROOT . '/' . $directory;

            foreach ($fs->files($directory) as $file) {
                $shortClass = $fs->name($file);
                $class = $namespace . '\\' .  $shortClass;
                $mapClass = $mapNamespace . '\\' . $shortClass . $mapSuffix;
                $inspector->map($class, $mapClass);
            }
        }
    }


}