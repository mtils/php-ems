<?php


namespace Ems\Contracts\XType;

interface Formatter
{
    /**
     * Format the $path of $object fir $view in $lang.
     * This Formatter has to support $paths of properties like "title" but also
     * nested like $user, 'address.street'
     *
     * @param object $object
     * @param string $path The path. Can be a property name or a nested path
     * @param string $view (optional)
     * @param string $lang (optional)
     * @return string
     **/
    public function format($object, $path, $view='default', $lang=null);
}
