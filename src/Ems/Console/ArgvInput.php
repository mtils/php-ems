<?php
/**
 *  * Created by mtils on 15.09.19 at 18:07.
 **/

namespace Ems\Console;


use Ems\Core\Input;

class ArgvInput extends Input
{
    /**
     * @var array
     */
    private $argv;

    public function __construct(array $argv=[])
    {
        parent::__construct();
        $this->argv = $argv ?: $_SERVER['argv'];
    }

    /**
     * @return array
     */
    public function getArgv()
    {
        return $this->argv;
    }

    /**
     * @param array $argv
     * @return ArgvInput
     */
    public function setArgv(array $argv)
    {
        $this->argv = $argv;
        return $this;
    }

    /**
     * Return the value of console argument named $name.
     * The input has to be routed to support that.
     *
     * @param string $name
     * @param mixed $default (optional)
     *
     * @return mixed
     */
    public function argument($name, $default=null)
    {

    }

    /**
     * Return the value of console option named $name.
     * The input has to be routed to support that.
     *
     * @param string $name
     * @param mixed  $default (optional)
     */
    public function option($name, $default=null)
    {

    }

}