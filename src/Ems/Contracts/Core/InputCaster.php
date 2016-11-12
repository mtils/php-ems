<?php

namespace Ems\Contracts\Core;

interface InputCaster extends NamedCallableChain
{
    /**
     * After the input data is validated perform this method to cast the
     * data for use in your repositories/model/database. Here you would
     * remove _confirmation fields.
     *
     * @param array  $input
     * @param array  $metadata     (optional)
     * @param string $resourceName (optional)
     *
     * @return array The corrected data
     **/
    public function cast(array $input, array $metadata = [], $resourceName = null);
}
