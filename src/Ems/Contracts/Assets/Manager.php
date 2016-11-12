<?php

namespace Ems\Contracts\Assets;

use Ems\Contracts\Core\Configurable;
use Ems\Contracts\Core\Copyable;

/**
 * The Manager is basically a facade for easier usage of
 * the whole orchestrated bundle of classes. It should be used in
 * the view.
 *
 * Supported attributes for replicate should be (groupPrefix)
 **/
interface Manager extends Registrar, Configurable, Copyable
{
    /**
     * This is an option for BuildConfig::setManagerOptions().
     *
     * this is bool and means that when it finds a compiled file for a group
     * it does not call the registry to get the assigned files and merges them
     * with the compiled files.
     * If you really know that all files your application needs inside a asset
     * group this will result in faster rendering
     *
     * @var string
     **/
    const MERGE_UNCOMPILED_FILES = 'merge_uncompiled_files';

    /**
     * This is an option for BuildConfig::setManagerOptions().
     *
     * This is bool and determines that the manager should check if a compiled
     * file exists before if deceides to use it.
     * One file check per group is omitted per request (performance)
     *
     * @var string
     **/
    const CHECK_COMPILED_FILE_EXISTS = 'check_compiled_file_exists';

    /**
     * Renders group $group.
     *
     * @param string $group
     *
     * @return \Ems\Contracts\Assets\Collection
     **/
    public function render($group);

    /**
     * Render a group with a custom callable. Signature is:.
     *
     * function (string $group, Registry $registry) {}
     *
     * This method is for compilers and similar classes that wants to break the
     * whole process and return a single compiled file.
     *
     * For performance reasons the manager must not do anything, not even get
     * the collection from the Registry. The compiler has to do that itself if
     * it needs to.
     *
     * The custom renderer has to return \Ems\Contracts\Assets\Collection
     *
     * @param string   $groupName
     * @param callable $renderer
     *
     * @return self
     **/
    public function renderGroupWith($groupName, callable $renderer);

    /**
     * If a group prefix is set the manager will prefix all detected groups
     * with that prefix. Since this property is immutable you can only use a
     * replicated version to apply a groupPrefix.
     *
     * This is handy for vendor based templates/assets. So you can pass a 
     * namespaced registry to the view and can write just the same code as
     * there where no namespace in your templates
     *
     * @example $copy = $manager->replicate(['groupPrefix'=>'mylib']);
     *          $copy->import('scroll.js');
     *          // Same as $manager->import('scroll.js', 'mylib')
     *          $copy->import('scroll.js', 'common');
     *          // Same as $manager->import('scroll.js', 'mylib.common')
     *
     * @return string
     **/
    public function groupPrefix();
}
