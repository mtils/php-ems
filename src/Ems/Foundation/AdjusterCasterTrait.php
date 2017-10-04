<?php

namespace Ems\Foundation;

use Ems\Contracts\Foundation\InputProcessor as InputProcessorContract;

trait AdjusterCasterTrait
{
    /**
     * @var InputProcessorContract
     **/
    protected $adjuster;

    /**
     * @var InputProcessorContract
     **/
    protected $caster;

    /**
     * @return InputProcessorContract
     **/
    public function getAdjuster()
    {
        return $this->adjuster;
    }

    /**
     * {@inheritdoc}
     *
     * @param InputProcessorContract $adjuster
     *
     * @return self
     **/
    public function setAdjuster(InputProcessorContract $adjuster)
    {
        $this->adjuster = $adjuster;
        return $this;
    }

    /**
     * @return InputProcessorContract
     **/
    public function getCaster()
    {
        return $this->caster;
    }

    /**
     * @param InputProcessorContract $caster
     *
     * @return self
     **/
    public function setCaster(InputProcessorContract $caster)
    {
        $this->caster = $caster;
        return $this;
    }

}
