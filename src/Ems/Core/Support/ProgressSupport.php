<?php
/**
 *  * Created by mtils on 02.02.18 at 14:42.
 **/

namespace Ems\Core\Support;


use function call_user_func;
use Ems\Contracts\Core\Progress;

/**
 * Trait ProgressSupport
 *
 * @package Ems\Core\Support
 *
 * @see \Ems\Contracts\Core\PublishesProgress
 */
trait ProgressSupport
{
    /**
     * @var array
     */
    protected $progressListeners = [];

    /**
     * Call a listener when the progress of the implementing
     * owner changes. The $listener will be called with a
     * Progress object.
     *
     * @param callable $listener
     *
     * @see Progress
     */
    public function onProgressChanged(callable $listener)
    {
        $this->progressListeners[] = $listener;
    }

    /**
     * Emit a progress object to all listeners.
     *
     * @param Progress|int $progress
     * @param int    $step       (default:0)
     * @param int    $totalSteps (default:1)
     * @param string $stepName   (optional)
     */
    protected function emitProgress($progress, $step=0, $totalSteps=1, $stepName='')
    {

        if (!$progress instanceof Progress) {
            $progress =  new Progress($progress, $step, $totalSteps, $stepName);
        }

        foreach ($this->progressListeners as $listener) {
            call_user_func($listener, $progress);
        }
    }

}