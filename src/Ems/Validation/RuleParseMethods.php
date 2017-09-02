<?php


namespace Ems\Validation;


trait RuleParseMethods
{
    /**
     * Split the rules in an array
     *
     * @param array|string $keyRules
     *
     * @return array
     **/
    protected function explodeRules($keyRules)
    {
        return is_string($keyRules) ? explode('|', $keyRules) : (array)$keyRules;
    }

    /**
     * Split a rule into its name and an array of parameters
     *
     * @param string $rule
     *
     * @return array
     **/
    protected function ruleAndParameters($rule)
    {
        if (!strpos($rule, ':')) {
            return [$this->normalizeRuleName($rule), []];
        }

        list($ruleName, $parameters) = explode(':', $rule, 2);

        return [$this->normalizeRuleName($ruleName), explode(',', $parameters)];
    }

    /**
     * Ensure underscores
     *
     * @param string
     *
     * @return string
     **/
    protected function normalizeRuleName($ruleName)
    {
        return str_replace('-', '_', mb_strtolower($ruleName));
    }
}
