<?php


namespace Ems\Contracts\Assets;

use Ems\Contracts\Model\Repository;

interface BuildConfigRepository extends Repository
{
    /**
     * Return an array of all assigned groups (names)
     *
     * @return array
     **/
    public function groups();

    /**
     * Return if this repository has $group
     *
     * @return array
     **/
    public function has($group);

    /**
     * Return if the compiled file of $config exists
     *
     * @param \Ems\Contracts\Assets\BuildConfig $config
     * @return bool
     **/
    public function compiledFileExists(BuildConfig $config);

    /**
     * This is a method to bind callables which will be called once to fill this
     * repository. This is usefull because often the filling has to be late in
     * the application cycle.
     * You must support multiple listeners
     *
     * @param callable
     * @return self
     */
    public function fillRepositoryBy(callable $filler);

}
