<?php

namespace Ems\Contracts\Foundation;

use Ems\Contracts\Core\NamedCallableChain;
use Ems\Contracts\Core\AppliesToResource;

/**
 * An input processor sorts/casts/corrects input to work with it inside your
 * application.
 * This is generally an approach to remove code from controllers.
 **/
interface InputProcessor extends NamedCallableChain
{
    /**
     * Sort. rearrange, cast, whatever do with the input array.
     *
     * @param array             $input
     * @param AppliesToResource $resource (optional)
     *
     * @return array
     **/
    public function process(array $input, AppliesToResource $resource = null);
}
