# XType Class and Interface Hierarchy

The basic concept of XType is to centrally store all Information about a type. In Java or python you could follow the principle "wrap your atomic values" which basically means you should build your own scalar types like CustomInt or HtmlString.

With operator overloading this could make sense but also introduces a lot of overhead. In true compiled Languages like C++ or Java and duck typed languages like python this is done often but it is very rarely done in php.

XType classes are basically data objects with a hierarchy. They store the information about a type in public properties. You can get the xtype of a value by asking a TypeProvider ($provider->xType(User::class, 'email').

## The basic hierarchy


  * AbstractType
    * BoolType
    * NumberType
        * UnitType
    * StringType
    * TemporalType
    * SequenceType
    * KeyValueType
        * ArrayAccessType
        * ObjectType

Beside this classes there are the following constraint interfaces:
HasMinMax, MustBeInList, CustomContraint

### HasMinMax
This interface says that the type has a min/max contraint. It has a
min and max property.

### MustBeInList
This interface says that the value of this variable can only be inside a list. Like an enumeration or a foreign key. It has a public allow property which
contains all allowed values

### CustomConstraint
This interface means that the xtype defines its own constraint and can check values itself. This is usefull to allow custom string types with regex or something like that.

## AbstractType
The abstract type defines the following properties:

  * canBeNull
  * defaultValue
  * mustBeTouched
  * readonly

All properties should be self-describing. The only confusing is mustBeTouched, wich says the user has to touch the value but it can be null.

## Type Hierarchy with Interfaces

 * AbstractType (abstract)
    * BoolType (MustBeInList:true/false)
    * NumberType (HasMinMax)
        * UnitType
    * StringType (HasMinMax)
    * TemporalType
    * SequenceType (HasMinMax)
    * KeyValueType
        * ArrayAccessType
        * ObjectType

Because XType are basically just data objects, too my opinion there is no need to put every class into its own file. Because the most extended class are just 3 lines of code this a unnecessary overhead.

## Type Hierarchy

Now follows the whole type tree

 * AbstractType (abstract)
    * BoolType (MustBeInList:true/false)
    * NumberType (HasMinMax)
        * UnitType
            * MoneyType
            * MemoryType
            * SpatialType
               * DistanceType
               * AreaType
            * TemporalUnitType
    * StringType (HasMinMax)
    * TemporalType
    * SequenceType (HasMinMax)
    * PairType
        * RangeType (because there is one itemType for from/to)
    * KeyValueType
        * ArrayAccessType
        * ObjectType


XType also produces some other benefits. It holds the information about a variable (or property) that allows you to build validation, database tables out of it and format a value according to its xtype.

## Aliases and XType names

The TypeFactory allows extending it by callables. The classes in XType are
used to build a hierarchy for basic grouping (DistanceType is a SpacialType is a UnitType is a NumberType).

Checking for the right type should usually be done by checking its name. ($type->getName() == 'string').

You can add shortcuts by extending TypeFactory:

```php
<?php

use Ems\Contracts\XType\TypeFactory;
use Ems\Core\IOCContainer;


$ioc = new IOCContainer;

$ioc(TypeFactory::class)->extend('pet_name', function ($name, TypeFactory $factory) {
    return new StringType(['min'=>3, 'max'=>32]);
});

// This returns a cloned instance of the created StringType inside the closure
$type = $ioc(TypeFactory::class)->toType('pet_name');

```

## Predefined aliases

The following aliases are already defined in ems:

### StringType

* url
* url_segment
* uri
* domain
* filepath
* filename
* email
* binary
* mimetype
* language_code2
* country_code_2
* language_code3
* country_code_3
* postcode
* location
* street
* house_number
* epsg_code
* credit_card_number
* hash
* abbrevation
* locale
* expression

### NumberType

* database_id
* foreign_key

### UnitType

* temperature
* weight
* speed
* bytes
* frequency
* pressure
* energy
* angle
* quantity
* count

### SpacialType

* longitude
* latitude
* altitude

### TemporalType

* future_date
* past_date
* date
* datetime
* time
