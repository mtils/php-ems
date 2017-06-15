<?php

namespace Ems\Foundation;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Foundation\InputProcessor as InputProcessorContract;
use Ems\Core\Support\ProvidesNamedCallableChain;

class InputProcessor implements InputProcessorContract
{
    use ProvidesNamedCallableChain;

    /**
     * {@inheritdoc}
     *
     * @param array             $input
     * @param AppliesToResource $resource (optional)
     *
     * @return array
     **/
    public function process(array $input, AppliesToResource $resource = null)
    {
        $corrected = $input;

        foreach ($this->buildChain() as $name => $parameters) {
            $corrector = $this->getExtension($name);
            $result = $corrector($corrected, $resource);
            $corrected = &$result;
        }

        return $corrected;
    }
}
