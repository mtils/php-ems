<?php
/**
 *  * Created by mtils on 22.08.18 at 13:31.
 **/

namespace Ems\Core;

use Ems\Contracts\Core\Input as InputContract;
use Ems\Contracts\Core\None;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Support\FastArrayDataTrait;
use Ems\Core\Support\InputTrait;
use Ems\Core\Support\MessageTrait;
use Ems\Core\Support\RoutableTrait;
use function array_key_exists;

class Input implements InputContract
{
    use FastArrayDataTrait;
    use RoutableTrait;
    use MessageTrait;
    use InputTrait;

    /**
     * Input constructor.
     *
     * @param array $parameters
     */
    public function __construct($parameters=[])
    {
        $this->_attributes = $parameters;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $key
     * @param mixed $default (optional)
     *
     * @return mixed
     **/
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->_attributes)) {
            return $this->_attributes[$key];
        }
        return $default;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $key
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     *
     * @return mixed
     **/
    public function getOrFail($key)
    {
        $result = $this->get($key, new None());

        if ($result instanceof None) {
            throw new KeyNotFoundException("Key '$key' not found in Input");
        }

        return $result;
    }


}