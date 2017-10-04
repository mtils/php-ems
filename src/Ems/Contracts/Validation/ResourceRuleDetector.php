<?php


namespace Ems\Contracts\Validation;

use Ems\Contracts\Core\AppliesToResource;

interface ResourceRuleDetector
{
    /**
     * Convert a resource into a rule array.
     *
     * @param AppliesToResource $resource
     * @param int|array         $relationDepth (default:1)
     *
     * @return array
     **/
    public function detectRules(AppliesToResource $resource, $relationDepth=1);
}
