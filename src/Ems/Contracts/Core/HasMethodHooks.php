<?php

namespace Ems\Contracts\Core;

/**
 * If your object supports hooks, implement this interface.
 **/
interface HasMethodHooks extends Hookable
{
    /**
     * Return an array of methodnames which can be hooked via
     * onBefore and onAfter.
     *
     * @return array
     **/
    public function methodHooks();
}
