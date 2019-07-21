<?php
/**
 *  * Created by mtils on 20.07.19 at 07:30.
 **/

namespace Ems\Contracts\Core;

use function call_user_func;

/**
 * Class Positioner
 *
 * This class is for all classes that allow fluid calls like this:
 *
 * $class->add($whatever)->after($whateverElse)
 *
 * Just instantiate a positioner in you method (like above in add()) and give it
 * a handle (to remember what was added) and give it two callables to perform
 * the real insertion.
 *
 * @package Ems\Contracts\Core
 */
class Positioner
{
    /**
     * @var mixed
     */
    protected $handle;

    /**
     * @var callable
     */
    protected $beforeCallback;

    /**
     * @var callable
     */
    protected $afterCallback;

    /**
     * Positioner constructor.
     *
     * @param mixed    $handle
     * @param callable $beforeCallback
     * @param callable $afterCallback
     */
    public function __construct($handle, callable $beforeCallback, callable $afterCallback)
    {
        $this->handle = $handle;
        $this->beforeCallback = $beforeCallback;
        $this->afterCallback = $afterCallback;
    }

    /**
     * Position the previous added thing before the passed one
     *
     * $list->add('sylvia')->before('thomas')
     *
     * @param mixed $other
     *
     * @return void
     */
    public function before($other)
    {
        call_user_func($this->beforeCallback, $this->handle, $other);
    }

    /**
     * Position the previous added thing before the passed one
     *
     * $list->add('thomas')->after('sylvia')
     *
     * @param mixed $other
     *
     * @return void
     */
    public function after($other)
    {
        call_user_func($this->afterCallback, $this->handle, $other);
    }
}