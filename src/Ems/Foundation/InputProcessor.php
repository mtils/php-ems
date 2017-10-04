<?php

namespace Ems\Foundation;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Foundation\InputProcessor as InputProcessorContract;
use Ems\Core\Support\ProvidesNamedCallableChain;
use Ems\Core\Lambda;

class InputProcessor implements InputProcessorContract
{

    use ProvidesNamedCallableChain;

    /**
     * {@inheritdoc}
     *
     * @param array             $input
     * @param AppliesToResource $resource (optional)
     * @param string $locale (optional)
     *
     * @return array
     **/
    public function process(array $input, AppliesToResource $resource = null, $locale = null)
    {
        $corrected = $input;

        foreach ($this->buildChain() as $name => $options) {

            $result = Lambda::callMerged(
                $this->getExtension($name),
                [
                    'input'    => $corrected,
                    'resource' => $resource,
                    'locale'   => $locale
                ],
                $options['parameters']
            );

            $corrected = &$result;
        }

        return $corrected;
    }

}
