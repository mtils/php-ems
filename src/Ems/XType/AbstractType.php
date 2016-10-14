<?php


namespace Ems\XType;

use Ems\Contracts\XType\XType;
use Ems\Core\Exceptions\UnsupportedParameterException;
use ReflectionObject;
use ReflectionProperty;
use Closure;
use JsonSerializable;


abstract class AbstractType implements XType
{

    /**
     * @var bool
     **/
    public $canBeNull = true;

    /**
     * @var mixed
     **/
    public $defaultValue;

    /**
     * @var bool
     **/
    public $mustBeTouched = false;

    /**
     * @var bool
     **/
    public $readonly = false;

    /**
     * @var array
     **/
    protected static $propertyMap = [];

    /**
     * @var array
     **/
    protected static $aliases = [];


    /**
     * @param array $attributes (optional)
     **/
    public function __construct(array $attributes=[])
    {


        if (!isset(static::$propertyMap[static::class])) {
            static::$propertyMap[static::class] = $this->buildPropertyMap();
        }

        if (!isset(static::$aliases[static::class])) {
            static::$aliases[static::class] = $this->aliases();
        }

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
     * @return self
     * @throws \Ems\Contracts\Core\Unsupported
     **/
    public function fill(array $attributes=[])
    {

        foreach ($attributes as $property=>$value) {

            if (isset(static::$propertyMap[static::class][$property])) {
                $this->{$property} = $value;
                continue;
            }

            if (!isset(static::$aliases[static::class][$property])) {
                throw new UnsupportedParameterException("$property is not supported in " . static::class);
            }

            $alias = static::$aliases[static::class][$property];

            if ($alias instanceof Closure) {
                $alias($this, $property, $value);
                continue;
            }

            $this->{$alias} = $value;

        }

        return $this;

    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     * @return self
     * @see \Ems\Contracts\Core\Copyable
     **/
    public function replicate(array $attributes=[])
    {
        return new static(array_merge($this->toArray(), $attributes));
    }

    /**
     * Return if this is a complex type (array or class)
     *
     * @return bool
     **/
    public function isComplex()
    {
        return in_array($this->group(), [self::CUSTOM, self::COMPLEX]);
    }

    /**
     * Return if this type is scalar
     *
     * @return bool
     **/
    public function isScalar()
    {
        return in_array($this->group(), [self::NUMBER, self::STRING, self::BOOL]);
    }

    /**
     * Return the xtype properties as an array
     *
     * @return array
     **/
    public function toArray()
    {
        $array = [];

        foreach (static::$propertyMap[static::class] as $name=>$property) {
            $array[$name] = $this->{$name};
        }

        return $array;
    }

    /**
     * Return a set of aliases to support shortcuts to some public properties.
     * Dont forget to call parent::aliases
     *
     * @return array
     **/
    protected function aliases()
    {

        $cannotBeNull = function ($type, $property, $value) {
            $type->canBeNull = !$value;
        };

        $ignore = function ($type, $property, $value) {
            $type->mustBeTouched = !$value;
        };

        return [
            'required'  => $cannotBeNull,
            'null'      => 'canBeNull',
            'optional'  => 'canBeNull',
            'touched'   => 'mustBeTouched',
            'ignore'    => $ignore,
            'ignored'   => $ignore,
            'protected' => 'readonly',
            'forbidden' => 'readonly'
        ];
    }

    /**
     * Returns a lookup array of all public properties as keys
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

}
