<?php

namespace Ems\Contracts\XType;

use Ems\Contracts\Core\Copyable;

/**
 * XType is an extended type definition. Describe
 * any type with a xtype and you can build validation,
 * formatting and casting out of it.
 * The magic interface is for getting and setting null|min/max...values
 * If a property is not known throw a Ems\Core\Unsupported.
 * Since you usally build such a type once and dont change it, it is only a
 * fill() interface provided.
 * All supported properties have to be added as public(!) properties for performance
 * reasons.
 * The XType should only act like an array with inheritance
 * It is recommended (but not forced) to typehint against the AbstractType
 * class instead of this interface. This may break SOLID but an xtype is data
 * not logic.
 * This interface is just for not putting the constants in the abstract class
 * and to remain as SOLID as possible.
 **/
interface XType extends Copyable
{
    /**
     * A constant null type.
     **/
    const NONE = 'null';

    /**
     * A custom type. The only custom type in php is a class.
     **/
    const CUSTOM = 'custom';

    /**
     * A number type like float, int, double.
     **/
    const NUMBER = 'number';

    /**
     * A string.
     */
    const STRING = 'string';

    /**
     * A bool type.
     **/
    const BOOL = 'boolean';

    /**
     * A complex type like array, sequence, dictionary, collection.
     **/
    const COMPLEX = 'complex';

    /**
     * A temporal type like DateTime, Date, Time, TimeRange.
     **/
    const TEMPORAL = 'temporal';

    /**
     * A binary type (nativly mostly string).
     **/
    const BINARY = 'binary';

    /**
     * A resource (like the result of fopen()).
     **/
    const RESOURCE = 'resource';

    /**
     * Return the group of this type (see self::NONE, self::CUSTOM.
     *
     * @return string
     **/
    public function group();

    /**
     * Fill this type with the passed attributes.
     *
     * @param array $attributes
     *
     * @throws \Ems\Contracts\Core\Unsupported
     *
     * @return self
     **/
    public function fill(array $attributes = []);

    /**
     * Return if this is a complex type (array or class).
     *
     * @return bool
     **/
    public function isComplex();

    /**
     * Return if this type is scalar.
     *
     * @return bool
     **/
    public function isScalar();

    /**
     * Get the name of this type
     *
     * @return string
     **/
    public function getName();

    /**
     * Set a name for this type. This is used to distinguish types and not
     * to write classes for every little type. (e.g. email for a string type...)
     *
     * @param string $name
     *
     * @return self
     **/
    public function setName($name);

}
