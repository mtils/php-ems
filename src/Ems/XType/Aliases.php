<?php

namespace Ems\XType;

use Ems\Contracts\Core\Extendable;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Exceptions\MisConfiguredException;

/**
 * This class is used to register all needed XType aliases.
 **/
class Aliases
{
    /**
     * @var array
     **/
    protected $aliases = [
        'string' => [
            'url' => 'min:1|max:255',
            'url_segment' => 'min:1|max:128',
            'uri' => 'min:1|max:255',
            'domain' => 'min:1|max:63',
            'filepath' => 'min:1|max:255',
            'filename' => 'min:1|max:128',
            'email' => 'min:3|max:254',
            'ip' => 'min:7|max:39',
            'ipv4' => 'min:7|max:15',
            'ipv6' => 'min:15|max:39',
            'json' => 'min:1|content_type:application/json',
            'binary' => 'min:1|content_type:application/octet-stream',
            'serialized' => 'min:1|content_type:application/vnd.php.serialized',
            'mimetype' => 'min:3|max:254',
            'language_code2' => 'min:2|max:2',
            'country_code2' => 'min:2|max:2',
            'language_code3' => 'min:3|max:3',
            'country_code3' => 'min:3|max:3',
            'locale' => 'min:5|max:5',
            'postcode' => 'min:2|max:16',
            'location' => 'min:1|max:85',
            'street' => 'min:1|max:72',
            'house_number' => 'min:1|max:15', // for the range postcodes: 6 + ' - ' + 6
            'phone_number' => 'min:3|max:32', // for format; 20 + some _,+,/...
            'epsg_code' => 'min:4|max:5',
            'credit_card_number' => 'min:13|max:22',
            'hash' => 'min:16|max:255',
            'abbrevation' => 'min:1|max:52',
            'expression' => 'min:3|max:255',
        ],
        'number' => [
            'int' => 'native_type:int|precision:0|decimal_places:0',
            'integer' => 'native_type:int|precision:0|decimal_places:0',
            'float' => 'native_type:float|precision:8|decimal_places:1',
            'double' => 'native_type:float|precision:8|decimal_places:1',
            'real' => 'native_type:float|precision:8|decimal_places:1',
            'database_id' => 'native_type:int|precision:0|decimal_places:0|readonly',
            'foreign_key' => 'native_type:int|precision:0|decimal_places:0',
        ],
        'unit' => [
            'temperature' => 'native_type:float|precision:4|decimal_places:1|unit:°',
            'weight' => 'native_type:float|precision:2|decimal_places:1|unit:kg',
            'speed' => 'native_type:float|precision:2|decimal_places:0|unit:km/h',
            'bytes' => 'native_type:int|precision:0|decimal_places:0|unit:MB',
            'frequency' => 'native_type:float|precision:2|decimal_places:1|unit:Hz',
            'pressure' => 'native_type:float|precision:4|decimal_places:2|unit:Pa',
            'energy' => 'native_type:float|precision:8|decimal_places:2|unit:kWh',
            'angle' => 'native_type:float|precision:4|decimal_places:1|unit:°',
            'quantity' => 'native_type:int|precision:0|decimal_places:0|unit:pcs',
            'count' => 'native_type:int|precision:0|decimal_places:0|unit:pcs',
        ],
        'position' => [
            'latitude' => 'native_type:float|precision:7|decimal_places:6',
            'longitude' => 'native_type:float|precision:7|decimal_places:6',
            'altitude' => 'native_type:float|precision:7|decimal_places:6',
        ],
        'distance' => [
            'length' => 'native_type:float|precision:4|decimal_places:2|unit:m',
        ],
        'temporal' => [
            'datetime' => 'precision:s',
            'date' => 'precision:d',
            'future' => 'precision:s|point_in_time:future',
            'past' => 'precision:s|point_in_time:past',
            'now' => 'precision:s|point_in_time:now',
            'future_date' => 'precision:d|point_in_time:future',
            'past_date' => 'precision:d|point_in_time:past',
            'today' => 'precision:d|point_in_time:now',
            'time' => 'precision:s|!absolute',
            'timestamp' => 'precision:s|readonly'
        ],
    ];

    /**
     * @var array
     **/
    protected $aliasCache;

    /**
     * Create a type of this aliases.
     *
     * @param string      $alias
     * @param TypeFactory $factory
     *
     * @return AbstractType
     **/
    public function toType($alias, TypeFactory $factory)
    {
        $typeRule = $this->findTypeRule($alias);
        $config = $typeRule['type'].'|'.$typeRule['rule'];

        return $factory->toType($config);
    }

    /**
     * Add the available aliases to factory $factory.
     *
     * @param Extendable $factory
     **/
    public function addTo(Extendable $factory)
    {
        foreach ($this->aliases as $typeName => $rules) {
            foreach ($rules as $alias => $rule) {
                $factory->extend($alias, [$this, 'toType']);
            }
        }
    }

    /**
     * Find type and rule for $name.
     *
     * @param string $name
     *
     * @return array
     **/
    protected function findTypeRule($name)
    {
        $this->buildCacheOnce();

        if (!isset($this->aliasCache[$name])) {
            throw new KeyNotFoundException("Type with name $name not found");
        }

        return $this->aliasCache[$name];
    }

    /**
     * Build a cache for faster name lookups.
     **/
    protected function buildCacheOnce()
    {
        if ($this->aliasCache !== null) {
            return;
        }

        $this->aliasCache = [];

        foreach ($this->aliases as $typeName => $rules) {
            foreach ($rules as $name => $rule) {
                if (isset($this->aliasCache[$name])) {
                    $firstTypeName = $this->aliasCache[$name]['type'];
                    throw new MisConfiguredException("Double entry for name '$name' (under $typeName and $firstTypeName)");
                }

                $this->aliasCache[$name] = [
                    'type' => $typeName,
                    'rule' => $rule,
                ];
            }
        }
    }
}
