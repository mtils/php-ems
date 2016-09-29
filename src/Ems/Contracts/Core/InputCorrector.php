<?php


namespace Ems\Contracts\Core;


interface InputCorrector extends NamedCallableChain
{
    /**
     * Correct the input so it can be processed by the validator. So at this
     * point dont remove _confirmed fields or cast anything to DateTime.
     * Just bring it in shape. Remove _method for example.
     *
     * @param array $input
     * @param array $metadata (optional)
     * @param string $resourceName (optional)
     * @return array The corrected data
     **/
    public function correct(array $input, array $metadata=[], $resourceName=null);
}
