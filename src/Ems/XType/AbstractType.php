<?php

namespace Ems\XType;

use BadMethodCallException;
use Ems\Contracts\Validation\Rule;
use Ems\Validation\Rule as Constraint;
use Ems\Contracts\XType\XType;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Helper;
use ReflectionObject;
use ReflectionProperty;
use ReflectionMethod;
use Closure;

abstract class AbstractType implements XType
{
    /**
     * @var bool
     **/
    //public $canBeNull = true;

    /**
     * @var mixed
     **/
    public $defaultValue;

    /**
     * @var bool
     **/
    public $readonly = false;

    /**
     * @var string
     **/
    protected $_name = '';

    /**
     * @var Constraint
     **/
    protected $constraints;

    /**
     * @var array
     **/
    protected static $propertyMap = [];

    /**
     * @var array
     **/
    protected static $getterMap = [];

    /**
     * @var array
     **/
    protected static $setterMap = [];

    /**
     * @var array
     **/
    protected static $aliases = [];

    /**
     * @param array $attributes (optional)
     **/
    public function __construct(array $attributes = [])
    {
        if (!isset(static::$propertyMap[static::class])) {
            static::$propertyMap[static::class] = $this->buildPropertyMap();
        }

        if (!isset(static::$aliases[static::class])) {
            static::$aliases[static::class] = $this->aliases();
        }

        if (!isset(static::$getterMap[static::class])) {
            list($getterMap, $setterMap) = $this->buildMethodMaps();
            static::$getterMap[static::class] = $getterMap;
            static::$setterMap[static::class] = $setterMap;
        }

        $this->constraints = new Constraint;

        $this->fill($attributes);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    abstract public function group();

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @throws \Ems\Contracts\Core\Unsupported
     *
     * @return self
     **/
    public function fill(array $attributes = [])
    {
        foreach ($attributes as $property => $value) {
            if (isset(static::$propertyMap[static::class][$property])) {
                $this->{$property} = $value;
                continue;
            }

            if (isset(static::$setterMap[static::class][$property])) {
                $this->__set($property, $value);
                continue;
            }

            if (!isset(static::$aliases[static::class][$property])) {
                $this->constraints->__set($property, $value);
                continue;
            }

            $alias = static::$aliases[static::class][$property];

            if ($alias instanceof Closure) {
                $alias($this, $property, $value);
                continue;
            }

            if (property_exists($this, $alias)) {
                $this->{$alias} = $value;
                continue;
            }

            // Because constraints needs to be removed on
            // false values, they can only be aliased with
            // closures (callables) so this line is only
            // included to document that
            // $this->__set($alias, $value);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $property
     *
     * @return mixed
     **/
    public function __get($property)
    {

        if (isset(static::$getterMap[static::class][$property])) {
            $method = static::$getterMap[static::class][$property];
            return $this->$method();
        }

        if (!$this->constraints->__isset($property)) {
            throw new KeyNotFoundException("Neither this type nor the constraint has a property named '$property'");
        }

        return $this->constraints->__get($property);

    }

    /**
     * {@inheritdoc}
     *
     * @param string $property
     * @param mixed  $value
     *
     * @return void
     **/
    public function __set($property, $value)
    {
        if ($property == 'constraints' && is_array($value)) {
            $this->constraints->fill($value);
            return;
        }

        if (isset(static::$setterMap[static::class][$property])) {
            $method = static::$setterMap[static::class][$property];
            $this->$method($value);
            return;
        }

        $this->constraints->__set($property, $value);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $property
     *
     * @return bool
     **/
    public function __isset($property)
    {
        if (isset(static::$getterMap[static::class][$property])) {
            return true;
        }
        return $this->constraints->__isset($property);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $property
     *
     * @return void
     **/
    public function __unset($property)
    {

        if (isset(static::$setterMap[static::class][$property])) {
            throw new BadMethodCallException("You cannot unset '$property', its handled by an getter");
        }

        $this->constraints->__unset($property);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @return self
     *
     * @see \Ems\Contracts\Core\Copyable
     **/
    public function replicate(array $attributes = [])
    {
        $copy = new static(array_merge($this->toArray(), $attributes));
        $copy->setConstraints(clone $this->getConstraints());
        return $copy;
    }

    /**
     * Return if this is a complex type (array or class).
     *
     * @return bool
     **/
    public function isComplex()
    {
        return in_array($this->group(), [self::CUSTOM, self::COMPLEX]);
    }

    /**
     * Return if this type is scalar.
     *
     * @return bool
     **/
    public function isScalar()
    {
        return in_array($this->group(), [self::NUMBER, self::STRING, self::BOOL]);
    }

    /**
     * Return the xtype properties as an array.
     *
     * @return array
     **/
    public function toArray()
    {
        $array = ['name' => $this->getName()];

        foreach (static::$propertyMap[static::class] as $name => $property) {
            $value = $this->{$name};
            $array[$name] = $value instanceof XType ? $value->toArray() : $value;
        }

        if (!count($this->constraints)) {
            return $array;
        }

        $array['constraints'] = [];

        foreach ($this->constraints as $name=>$parameters) {
            $array['constraints'][$name] = $parameters;
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function getName()
    {
        if (!$this->_name) {
            $class = Helper::withoutNamespace($this);
            $this->_name = Helper::rtrimWord(Helper::snake_case($class, '-'), '-type');
        }

        return $this->_name;

    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return self
     **/
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return Rule
     **/
    public function getConstraints()
    {
        return $this->constraints;
    }

    /**
     * {@inheritdoc}
     *
     * @see self::getConstraints()
     *
     * @param Rule $constraint
     *
     * @return self
     **/
    public function setConstraints(Rule $constraint)
    {
        $this->constraints = $constraint;
        return $this;
    }

    /**
     * Return a set of aliases to support shortcuts to some public properties.
     * Dont forget to call parent::aliases.
     *
     * @return array
     **/
    protected function aliases()
    {

        $canBeNull = function ($type, $property, $value) {

            $constraints = $type->getConstraints();

            if (!$value) {
                $constraints->__set('not_null', true);
                return;
            }

            if ($constraints->__isset('not_null')) {
                $constraints->__unset('not_null');
            }

        };

        $required = function ($type, $property, $value) use ($canBeNull) {
            return $canBeNull($type, $property, !$value);
        };

        return [
            'required'  => $required,
            'canBeNull' => $canBeNull,
            'null'      => $canBeNull,
            'optional'  => $canBeNull,
            'protected' => 'readonly',
            'forbidden' => 'readonly'
        ];
    }

    /**
     * Returns a lookup array of all public properties as keys.
     *
     * @return array
     **/
    protected function buildPropertyMap()
    {
        $map = [];
        $properties = (new ReflectionObject($this))->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $map[$property->getName()] = $property;
        }

        return $map;
    }

    /**
     * Returns two lookup arrays of all public getters and setters.
     *
     * @return array
     **/
    protected function buildMethodMaps()
    {

        $getterMap = [];
        $setterMap = [];

        $methods = (new ReflectionObject($this))->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {

            if ($this->isGetter($method)) {
                $getterMap[$this->toPropertyName($method->getName())] = $method->getName();
                continue;
            }

            if ($this->isSetter($method)) {
                $setterMap[$this->toPropertyName($method->getName())] = $method->getName();
                continue;
            }


        }

        return [$getterMap, $setterMap];
    }

    /**
     * Return the property name of a method name.
     *
     * @param string $methodName
     *
     * @return string
     **/
    protected function toPropertyName($methodName)
    {

        if (strpos($methodName, 'get') === 0 || strpos($methodName, 'set') === 0) {
            return lcfirst(substr($methodName, 3));
        }

        if (strpos($methodName, 'is') === 0) {
            return lcfirst(substr($methodName, 2));
        }

        return $methodName;

    }

    protected function isGetter(ReflectionMethod $method)
    {
        if (!$method->isPublic() || $method->isStatic() || $method->isAbstract()) {
            return false;
        }
        return $method->getNumberOfParameters() == 0;
    }

    protected function isSetter(ReflectionMethod $method)
    {

        if (!$method->isPublic() || $method->isStatic() || $method->isAbstract()) {
            return false;
        }

        if ($this->toPropertyName($method->getName()) == $method->getName()) {
            return false;
        }

        return $method->getNumberOfParameters() != 0;
    }

    public function __clone()
    {
        $this->constraints = clone $this->constraints;
    }
}
