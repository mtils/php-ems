<?php


namespace Ems\XType;


class UnitType extends NumberType
{

    /**
     * The native unit, which is the unit you store in your database.
     * If you would store money as cents this would be cents.
     * When you format the values you could recalculate em
     *
     * @var string
     **/
    public $unit = '';

}
