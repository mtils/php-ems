<?php
/**
 *  * Created by mtils on 24.01.18 at 11:00.
 **/

namespace Ems\Contracts\Core;


interface PublishesProgress
{
    /**
     * Call a listener when the progress of the implementing
     * owner changes. The $listener will be called with a
     * Progress object.
     *
     * @param callable $listener
     *
     * @see Progress
     */
    public function onProgressChanged(callable $listener);
}