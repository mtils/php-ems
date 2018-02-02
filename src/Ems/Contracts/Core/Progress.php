<?php
/**
 *  * Created by mtils on 24.01.18 at 10:53.
 **/

namespace Ems\Contracts\Core;

/**
 * Class Progress
 *
 * This class is used to emit progress to the outside.
 *
 * @package Ems\Contracts\Core
 */
class Progress
{

    /**
     * Progress constructor.
     *
     * @param int    $percent    (default:0)
     * @param int    $step       (default:0)
     * @param int    $totalSteps (default:1)
     * @param string $stepName   (optional)
     */
    public function __construct($percent=0, $step=0, $totalSteps=1, $stepName='')
    {
        $this->percent = $percent;
        $this->step = $step;
        $this->totalSteps = $totalSteps;
        $this->stepName = $stepName;
    }

    /**
     * How many percent of 100 was currently processed.
     *
     * @var int
     */
    public $percent = 0;

    /**
     * Which step is currently processed or the last processed
     * step.
     *
     * @var int
     */
    public $step = 0;

    /**
     * The total amount of steps for the current operation.
     *
     * @var int
     */
    public $totalSteps = 1;

    /**
     * An internal (technical) name of the current step.
     *
     * @var string
     */
    public $stepName = 'step';

    /**
     * The estimated leftover time to complete the operation.
     *
     * @var int
     */
    public $leftOverSeconds = 0;
}