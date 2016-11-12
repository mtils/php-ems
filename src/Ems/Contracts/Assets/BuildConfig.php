<?php

namespace Ems\Contracts\Assets;

use Ems\Contracts\Core\Identifiable;

interface BuildConfig extends Identifiable
{
    /**
     * Return the group of this build config.
     *
     * @return string
     **/
    public function group();

    /**
     * Return the target file path where the build will be saved. This must
     * be a relative path which will be mapped to an absolute path by the
     * assigned mapper for this group (PathFinder|Registry).
     *
     * @return string
     **/
    public function target();

    /**
     * Return a asset collection of all assets. The assets has to have the
     * extact names you use in your templates to make skipped rendering
     * work.
     *
     * @return \Ems\Contracts\Assets\Collection
     **/
    public function collection();

    /**
     * Return an array of parser names which should parse the assets.
     *
     * @return array
     **/
    public function parserNames();

    /**
     * Return an array of parser options for parser $parserName.
     *
     * @param string $parserName (optional)
     *
     * @return array
     **/
    public function parserOptions($parserName = null);

    /**
     * Return an array of options for the manager.
     *
     * @return array
     **/
    public function managerOptions();

    /**
     * Return an array of options for the compiler.
     *
     * @return array
     **/
    public function compilerOptions();
}
