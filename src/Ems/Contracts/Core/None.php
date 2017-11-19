<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 19.11.17
 * Time: 08:16
 */

namespace Ems\Contracts\Core;

/**
 * Class None
 *
 * This class is for all cases where you want to know if something is really
 * nothing. If you want to now if something is a cache hit even if it is null
 * you can try: Cache::get('key', new None)
 * If you get that None back, you now that the entry does not exist.
 * You can also store None in an array to get rid of that isset($array['key'])
 * and null thing in php.
 *
 * @package Ems\Contracts\Core
 */
class None
{
    //
}