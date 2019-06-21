<?php
/**
 *  * Created by mtils on 11.06.19 at 21:36.
 **/

namespace Ems\XType\OrmObject;

use Ems\Contracts\Model\OrmObject;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\XType\ObjectType;
use function get_class;
use function is_object;
use function is_subclass_of;
use function method_exists;

class OrmObjectTypeFactory
{
    /**
     * @var array
     **/
    protected $typeCache = [];

    /**
     * @param OrmObject|string $ormObject
     */
    public function toType($ormObject)
    {
        list($instance, $class) = $this->instanceAndClass($ormObject);

        $config = $this->buildConfig($instance, $class);

        if ($type = $this->getFromCache($class)) {
            return $type;
        }
    }

    /**
     * Merge the autodetected rules with (optional) manually setted, translate
     * the relations and return the complete result.
     *
     * @param OrmObject  $instance
     * @param string $class
     *
     * @return array
     **/
    protected function buildConfig(OrmObject $instance, $class)
    {
        $manualRules = $this->getManualConfig($instance);
    }

    /**
     * Return the manually setted config of Model or an empty array
     *
     * @param OrmObject $model
     *
     * @return array
     **/
    protected function getManualConfig(OrmObject $model)
    {
        if (!method_exists($model, 'xTypeConfig')) {
            return [];
        }

        $config = $model->xTypeConfig();
        $parsed = [];
        $foreignKeys = [];

        foreach ($config as $key=>$rule) {
            if ($rule != 'relation') {
                $parsed[$key] = $rule;
                continue;
            }

            $relation = $this->relationReflector->buildRelationXTypeInfo($model, $key);

            $parsed[$key] = $relation['type'];

            if ($relation['foreign_keys']) {
                $foreignKeys += $relation['foreign_keys'];
            }
        }

        return $parsed;
    }

    /**
     * Return an instance of a model and a classname
     *
     * @param mixed $ormObject
     *
     * @return array
     **/
    protected function instanceAndClass($ormObject)
    {

        // Checks without instantiating first
        if (!is_subclass_of($ormObject, OrmObject::class)) {
            throw new UnsupportedParameterException('ModelTypeFactory only supports OrmObject models not '.Type::of($ormObject) );
        }

        return is_object($ormObject) ? [$ormObject, get_class($ormObject)] : [new $ormObject(), $ormObject];
    }

    /**
     * @param string $class
     *
     * @return ObjectType|null
     **/
    protected function getFromCache($class)
    {
        return isset($this->typeCache[$class]) ? $this->typeCache[$class] : null;
    }
}