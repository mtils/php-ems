<?php
/**
 *  * Created by mtils on 27.11.2021 at 08:42.
 **/

namespace Ems\Model;

use DateTime;

use function method_exists;
use function substr;

/**
 * This is class is used to generate default or update values for orm objects.
 */
class Generator
{
    /**
     * Create current timestamp
     */
    public const NOW = '$Now$';

    /**
     * Generate a value. Typically, use one of the constant values.
     *
     * @param string $name
     * @param array $args (optional)
     * @return mixed
     */
    public function make(string $name, array $args=[])
    {
        $method = $this->method($name);
        return $this->{$method}($args);
    }

    /**
     * Generate a value if the generator exists, otherwise return the passed value.
     * This is handy if you allow to store a value default that can be a generated
     * or static value.
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function makeOrReturn(string $name, array $args=[])
    {
        $method = $this->method($name);
        if (method_exists($this, $method)) {
            return $this->{$method}($args);
        }
        return $name;
    }

    /**
     * @return DateTime
     */
    public function generateNow() : DateTime
    {
        return new DateTime();
    }

    /**
     * Check if a generation method exists.
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name) : bool
    {
        return method_exists($this, $this->method($name));
    }

    protected function method(string $name)
    {
        return 'generate' . substr($name, 1, -1);
    }
}