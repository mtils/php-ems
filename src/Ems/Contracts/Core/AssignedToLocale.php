<?php
/**
 *  * Created by mtils on 01.12.18 at 07:23.
 **/

namespace Ems\Contracts\Core;

/**
 * Interface AssignedToLocale
 *
 * This interface is just to mark an object as assigned to a locale. If an
 * object has contents, a name, title or description you can mark them as in a
 * locale with this the getLocale() method.
 *
 * @package Ems\Contracts\Core
 */
interface AssignedToLocale
{
    /**
     * @return string
     */
    public function getLocale();
}