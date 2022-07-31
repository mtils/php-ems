<?php
/**
 *  * Created by mtils on 27.07.2022 at 21:31.
 **/

namespace Ems\Contracts\Model;

/**
 * Filterable is the simplistic Queryable. It does not support operators.
 *
 * $pool->filter(['foo' => 'bar])->filter('active', true)
 */
interface Filterable
{
    /**
     * Add a filter manually. Pass an array to add multiple values.
     *
     * @param string|array $key
     * @param mixed        $value (optional)
     *
     * @return Filterable|iterable
     */
    public function filter($key, $value=null);
}