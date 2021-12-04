<?php
/**
 *  * Created by mtils on 04.12.2021 at 12:41.
 **/

namespace Ems\Model;

use Ems\Contracts\Core\ObjectArrayConverter;
use Ems\Core\Exceptions\NotImplementedException;

use function get_object_vars;

class PropertyObjectArrayConverter implements ObjectArrayConverter
{
    /**
     * @var array
     */
    protected static $cache = [];

    /**
     * @param object $object
     * @param int $depth (default 0)
     * @return array
     */
    public function toArray($object, int $depth = 0) : array
    {
        if ($depth > 0) {
            throw new NotImplementedException('I understand only depths of 0');
        }
        $data = [];
        foreach (get_object_vars($object) as $property) {
            $data[$property] = $object->$property;
        }
        return $data;
    }

    /**
     * @param string $classOrInterface
     * @param array $data
     * @param bool $isFromStorage
     * @return mixed
     */
    public function fromArray(string $classOrInterface, array $data = [], bool $isFromStorage = false)
    {
        $object = new $classOrInterface;
        foreach (get_object_vars($object) as $property) {
            if (isset($data[$property])) {
                $object->$property = $data[$property];
            }
        }
        return $object;
    }

}