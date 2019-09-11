<?php

namespace Ems\Foundation;

use Ems\Contracts\Foundation\InputProcessor as InputProcessorContract;
use Ems\Core\Lambda;
use Ems\Core\Support\ProvidesNamedCallableChain;

class InputProcessor implements InputProcessorContract
{

    use ProvidesNamedCallableChain;

    /**
     * {@inheritdoc}
     *
     * @param array $input
     * @param object $resource (optional)
     * @param string $locale (optional)
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    public function process(array $input, $resource = null, $locale = null)
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
