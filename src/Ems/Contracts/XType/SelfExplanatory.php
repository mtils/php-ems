<?php

namespace Ems\Contracts\XType;


/**
 * Use this interface to describe your object by an xtype. This is useful for
 * orm models. So you can store all your type informations as an array.
 *
 * With this information you could build migrations, validation rules etc.
 * This interface is mainly used by TypeProvider (which uses TypeFactory)
 **/
interface SelfExplanatory
{
    /**
     * Return a xtype or an xtype (array) describing this class. If it returns
     * an XTYpe it has to be instanceof ObjectType
     *
     * @return array|\Ems\XType\ObjectType
     **/
    public function xTypeConfig();
}
