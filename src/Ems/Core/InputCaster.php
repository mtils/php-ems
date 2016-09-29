<?php


namespace Ems\Core;


use Ems\Contracts\Core\InputCaster as CasterContract;
use Ems\Core\Support\ProvidesNamedCallableChain;

class InputCaster implements CasterContract
{
    use ProvidesNamedCallableChain;

    /**
     * {@inheritdoc}
     *
     * @param array $input
     * @param array $metadata (optional)
     * @param string $resourceName (optional)
     * @return array The corrected data
     **/
    public function cast(array $input, array $metadata=[], $resourceName=null)
    {
        $corrected = $input;

        foreach ($this->chain as $casterName) {
            $result = call_user_func($this->casters[$casterName], $corrected, $metadata, $resourceName);
            $corrected = &$result;
        }

        return $corrected;

    }

}
