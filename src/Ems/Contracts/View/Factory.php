<?php


namespace Ems\Contracts\View;

interface Factory
{

    /**
     * Return if a view named $name exists
     *
     * @param string $name
     * @return bool
     **/
    public function exists($name);

    /**
     * Create an unrendered view
     *
     * @param string $name
     * @param array $data (optional)
     * @return \Ems\Contracts\View\View
     **/
    public function view($name, $data=[]);

    /**
     * Share a variable between all views
     *
     * @param array|string $key
     * @param mixed $value (optional)
     * @return self
     **/
    public function share($key, $value=null);

    /**
     * Register a callable on view names $names which
     * will be called with the view as its first argument and
     * the factory as its second argument
     *
     * @param array|string
     * @param callable $listener
     * @return self
     **/
    public function on($names, callable $listener);

}
