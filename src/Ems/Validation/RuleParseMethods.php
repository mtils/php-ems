<?php


namespace Ems\Validation;


trait RuleParseMethods
{

    /**
     * Overwrite this method to do some processing on the rules before
     * using them
     *
     * @param array $rules
     *
     * @return array
     **/
    protected function parseRules(array $rules)
    {
        $parsedRules = [];

        foreach ($rules as $key=>$keyRules) {
            $keyRuleArray = $this->explodeRules($keyRules);

            $parsedRules[$key] = [];

            foreach ($keyRuleArray as $keyRule) {
                list($ruleName, $parameters) = $this->ruleAndParameters($keyRule);
                $parsedRules[$key][$ruleName] = $parameters;
            }
        }

        return $parsedRules;
    }

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