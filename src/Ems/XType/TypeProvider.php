<?php


namespace Ems\XType;



use Ems\Contracts\Core\Extendable;
use Ems\Contracts\Core\Extractor;
use Ems\Contracts\XType\SelfExplanatory;
use Ems\Contracts\XType\HasTypedItems;
use Ems\Contracts\XType\TypeProvider as TypeProviderContract;
use Ems\Contracts\XType\XType;
use Ems\Core\Patterns\ExtendableByClassHierarchyTrait;




class TypeProvider implements TypeProviderContract
{
    use ExtendableByClassHierarchyTrait;

    /**
     * @var array
     **/
    protected $pathCache = [];

    /**
     * @var array
     **/
    protected $classCache = [];

    /**
     * @var Extractor
     **/
    protected $extractor;

    /**
     * @var TemplateTypeFactory
     **/
    protected $templateFactory;

    /**
     * @var TypeFactory
     **/
    protected $typeFactory;

    /**
     * @param Extractor           $extractor
     * @param TemplateTypeFactory $templateFactory
     * @param TypeFactory         $typeFactory
     **/
    public function __construct(Extractor $extractor, TemplateTypeFactory $templateFactory, TypeFactory $typeFactory)
    {
        $this->extractor = $extractor;
        $this->templateFactory = $templateFactory;
        $this->typeFactory = $typeFactory;
    }

    /**
     * Returns a xtype object for an object property. If path is null return the
     * Xtype for the whole class
     *
     * @param mixed   $root The resource name, class, an object of it or just some variable
     * @param string  $path (optional) A key name. Can be dotted like address.street.name
     *
     * @return XType|null
     **/
    public function xType($root, $path=null)
    {

        $class = is_object($root) ? get_class($root): $root;

        $isClass = is_string($class) && class_exists($class);

        if ($isClass && $type = $this->getClassXType($root, $class, $path)) {
            return $type;
        }

        if (!$isClass) {
            $value = $path ? $this->extractor->value($root, $path) : $root;
            return $this->templateFactory->toType($value);
        }

        // If the class exists but not the path dont return fake types
        if (isset($this->classCache[$class]) && $path) {
            return null;
        }

        $nativeType = $this->extractor->type($root, $path);

        return $this->nativeToXType($nativeType);

    }

    /**
     * Try to load a xtype of an object
     *
     * @param mixed  $root
     * @param string $class
     * @param string $path
     *
     * @return XType|null
     **/
    protected function getClassXType($root, $class, $path)
    {
        if ($type = $this->getFromCache($class, $path)) {
            return $type;
        }

        $root = is_object($root) ? $root : new $root;

        // If the class is in cache but not the specific path
        if (isset($this->classCache[$class])) {
            return null;
        }

        $classType = $root instanceof SelfExplanatory ?
                        $this->typeFactory->toType($root) :
                        $this->getFromExtension($class);

        if ($classType) {
            $this->putAllIntoCache($class, $classType);
            return $path ? $this->getClassXType($root, $class, $path) : $classType;
        }

        return null;
    }

    /**
     * Try to load the object type from an extension
     *
     * @param string $class
     *
     * @return XType|null
     **/
    protected function getFromExtension($class)
    {
        if (!$extension = $this->nearestForClass($class)) {
            return null;
        }

        return $extension($class);
    }

    /**
     * @param string $class
     * @param string $path
     *
     * @return XType|null
     **/
    protected function getFromCache($class, $path)
    {
        if (!isset($this->classCache[$class])) {
            return null;
        }

        if (!$path) {
            return $this->classCache[$class];
        }

        if (!isset($this->pathCache[$class])) {
            $this->putAllIntoCache($class, $this->classCache[$class]);
        }

        if (isset($this->pathCache[$class][$path])) {
            return $this->pathCache[$class][$path];
        }

        if (!strpos($path, '.')) {
            return null;
        }

        $segments = explode('.', $path);

        $type = null;
        $currentClass = $class;

        foreach ($segments as $segment) {

            if (!$type = $this->getFromCache($currentClass, $segment)) {
                 return null;
            }

            if ($type instanceof ObjectType) {
                $currentClass = $type->class;
            }

            if (!$type instanceof HasTypedItems) {
                continue;
            }

            if ($type->itemType instanceof ObjectType) {
                $currentClass = $type->itemType->class;
            }

        }

        return $type;

    }

    /**
     * Put the type for $class and $path into cache and return it
     *
     * @param string $class
     * @param string $path
     * @param XType  $type
     *
     * @return XType
     **/
    protected function putIntoCache($class, $path, XType $type)
    {
        if (!isset($this->pathCache[$class])) {
            $this->pathCache[$class] = [];
        }

        $this->pathCache[$class][$path] = $type;

        return $type;
    }

    /**
     * Put all key types of $type into cache
     *
     * @param string $class
     * @param KeyValueType $type
     **/
    protected function putAllIntoCache($class, KeyValueType $type)
    {

        $this->classCache[$class] = $type;

        foreach ($type as $key=>$keyType) {

            $this->putIntoCache($class, $key, $keyType);

            if ($keyType instanceof ObjectType && $keyType->class) {
                $this->classCache[$keyType->class] = $keyType;
            }

            if (!$keyType instanceof HasTypedItems) {
                continue;
            }

            $itemType = $keyType->itemType;

            if ($itemType instanceof ObjectType && $itemType->class) {
                $this->classCache[$itemType->class] = $itemType;
            }

        }
    }

    /**
     * Convert a native type (retrieved by gettype()) into an XType
     *
     * @param string $nativeType
     *
     * @return XType
     **/
    protected function nativeToXType($nativeType)
    {
        if ($nativeType === null) {
            return new StringType();
        }

        if (class_exists($nativeType)) {
            return new ObjectType(['class'=>$nativeType]);
        }

        if (in_array($nativeType, ['int', 'integer'])) {
            return new NumberType(['nativeType'=>'int']);
        }

        if (in_array($nativeType, ['float', 'double'])) {
            return new NumberType(['nativeType'=>'float']);
        }

        if (in_array($nativeType, ['bool', 'boolean'])) {
            return new BoolType;
        }

        if ($nativeType == 'array') {
            return new ArrayAccessType;
        }

        return new StringType;

    }

}
