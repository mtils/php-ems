<?php

namespace Ems\Contracts\View;

interface Factory
{
    /**
     * Return if a view named $name exists.
     *
     * @param string $name
     *
     * @return bool
     **/
    public function exists(string $name) : bool;

    /**
     * Create an unrendered view.
     *
     * @param string $name
     * @param array  $data (optional)
     *
     * @return View
     **/
    public function view(string $name, array $data = []) : View;

    /**
     * Share a variable between all views.
     *
     * @param array|string $key
     * @param mixed        $value (optional)
     *
     * @return self
     **/
    public function share($key, $value = null) : Factory;

}
