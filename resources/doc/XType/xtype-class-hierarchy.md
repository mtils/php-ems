# XType Class and Interface Hierarchy

The basic concept of XType is to centrally store all Information about a type. In Java or python you could follow the principle "wrap your atomic values" which basically means you should build your own scalar types like CustomInt or HtmlString.

With operator overloading this could make sense but also introduces a lot of overhead. In true compiled Languages like C++ or Java and duck typed languages like python this is done often but it is very rarely done in php.

XType classes are basically data objects with a hierarchy. They store the information about a type in public properties. You can get the xtype of a value by asking a XTypeProvider ($provider->keyType(User::class, 'email').

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

## Type Hierarchy Plan

Now follows the whole type plan, not all classes have been implemented yet

 * AbstractType (abstract)
    * BoolType (MustBeInList:true/false)
    * NumberType (HasMinMax)
        * UnitType
            * MoneyType
            * MemoryType
            * SpatialType
               * LengthType
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
