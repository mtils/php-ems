<?php

namespace Ems\Contracts\Assets;

interface Builder
{
    /**
     * Build $config.
     *
     * @param \Ems\Contracts\Assets\BuildConfig $config
     *
     * @return string the written path
     **/
    public function build(BuildConfig $config);

    /**
     * Get informed when a build was completed (per config)
     * The BuildConfig and the written path is passed to the listener.
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function whenBuilt(callable $listener);
}
