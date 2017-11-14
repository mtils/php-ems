<?php


namespace Ems\Validation\Illuminate;

use Ems\Contracts\Core\TextProvider;
use Ems\Contracts\Validation\Validation;
use Ems\Contracts\Validation\ValidationConverter as ConverterContract;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\MessageBag;


/**
 * This converter converts a validation into a message bag.
 *
 * Unfortunatly it is not completely possible to parse the messages outside
 * of laravels validator. The replacers are all protected....
 * This class does its best to parse the messages.
 **/
class ValidationConverter implements ConverterContract
{

    /**
     * @var TextProvider
     **/
    protected $textProvider;

    /**
     * The size related validation rules.
     *
     * @var array
     */
    protected $sizeRules = ['size', 'between', 'min', 'max'];

    /**
     * The numeric related validation rules.
     *
     * @var array
     */
    protected $numericRules = ['numeric', 'integer'];

    /**
     * Here are the special replacement placeholders.
     *
     * @var array
     **/
    protected $placeholders = [
        'between'            => ['min', 'max'],
        'digitsBetween'      => ['min', 'max'],
        'in'                 => ['values'],
        'notIn'              => ['values'],
        'mimes'              => ['values'],
        'requiredWith'       => ['values'],
        'requiredWithAll'    => ['values'],
        'requiredWithout'    => ['values'],
        'requiredWithoutAll' => ['values'],
        'requiredIf'         => ['other', 'value'],
        'requiredUnless'     => ['other', 'value'],
        'same'               => ['other'],
        'different'          => ['other'],
        'dateFormat'         => ['format'],
        'before'             => ['date'],
        'after'              => ['date'],
    ];

    /**
     * @var array
     **/
    protected $supportedFormats = [
        'Illuminate\Contracts\Support\MessageBag', // no ::class to work with laravel 5.1
        MessageBag::class
    ];

    /**
     * @param TextProvider $textProvider
     **/
    public function __construct(TextProvider $textProvider)
    {
        $this->textProvider = $textProvider;
//         dd($this->textProvider->getDomain());
    }

    /**
     * {@inheritdoc}
     *
     * @param Validation $validation
     * @param string     $format
     * @param array      $keyTitles (optional)
     * @param array      $customMessages (optional)
     *
     * @return mixed
     **/
    public function convert(Validation $validation, $format, array $keyTitles = [], array $customMessages = [])
    {

        if (!in_array($format, $this->supportedFormats)) {
            throw new UnsupportedParameterException(static::class . ' only supports MessageBag');
        }

        $messages = new MessageBag;

        if (!count($validation)) {
            return $messages;
        }

        foreach ($validation as $key=>$rules) {
            foreach ($rules as $ruleName=>$parameters) {
                $message = $this->getMessage($key, $ruleName, $rules, $keyTitles, $customMessages);
                $messages->add($key, $message);
            }
        }

        return $messages;

    }

    /**
     * Parse a message
     *
     * @param string $key            The array key
     * @param string $ruleName       The (translated) name of the rule
     * @param array  $keyRules       The rule array for one key
     * @param array  $keyTitles      Custom titles for the keys
     * @param array  $customMessages Custom messages (if passed)
     *
     * @return string
     **/
    protected function getMessage($key, $ruleName, array $keyRules, array $keyTitles, array $customMessages = [])
    {

        if (in_array($ruleName, $this->sizeRules)) {
            return $this->getSizeMessage($key, $ruleName, $keyRules, $keyTitles, $customMessages);
        }

        if ($customMessage = $this->getCustomMessage($key, $ruleName, $customMessages)) {
            return $customMessage;
        }

        $replacements = $this->replacements($ruleName, $keyRules);

        $replacements['attribute'] = isset($keyTitles[$key]) ? $keyTitles[$key] : $key;

        return $this->textProvider->get($ruleName, $replacements);

    }

    /**
     * Parse a size message. Special handling because of different messages for
     * strings, numbers, ...
     *
     * @param string $key            The array key
     * @param string $ruleName       The (translated) name of the rule
     * @param array  $keyRules       The rule array for one key
     * @param array  $keyTitles      Custom titles for the keys
     * @param array  $customMessages Custom messages (if passed)
     *
     * @return string
     **/
    protected function getSizeMessage($key, $ruleName, array $keyRules, array $keyTitles, array $customMessages)
    {
        $type = $this->getRuleType($keyRules);
        return $this->getMessage($key, "$ruleName.$type", $keyRules, $keyTitles, $customMessages);
    }

    /**
     * Classify the rule type
     *
     * @param array $keyRules
     *
     * @return string
     **/
    protected function getRuleType(array $keyRules)
    {
        // We assume that the attributes present in the file array are files so that
        // means that if the attribute does not have a numeric rule and the files
        // list doesn't have it we'll just consider it a string by elimination.
        if ($this->hasRule($keyRules, $this->numericRules)) {
            return 'numeric';
        }

        if ($this->hasRule($keyRules, 'array')) {
            return 'array';
        }

        if ($this->hasRule($keyRules, 'file')) {
            return 'file';
        }

        return 'string';
    }

    /**
     * Build the parameter replacements to parse the messages.
     *
     * @param string $ruleName
     * @param array  $keyRules
     *
     * @return array
     **/
    protected function replacements($ruleName, array $keyRules)
    {

        $ruleKey = str_contains($ruleName, '.') ? explode('.', $ruleName)[0] : $ruleName;

        // No parameters found ? give up
        if (!isset($keyRules[$ruleKey][0])) {
            return [];
        }

        if (!isset($this->placeholders[$ruleKey])) {
            return [$ruleKey => $keyRules[$ruleKey][0]];
        }

        $replacements = [];

        foreach ($this->placeholders[$ruleKey] as $i=>$name) {

            if (isset($keyRules[$ruleKey][$i])) {
                $replacements[$name] = $keyRules[$ruleKey][$i];
            }

        }

        return $replacements;
    }

    /**
     * Check if the passed rules contains on of $ruleNames
     *
     * @param array        $keyRules
     * @param string|array $ruleNames
     *
     * @return bool
     **/
    protected function hasRule(array $keyRules, $ruleNames)
    {

        $ruleNames = (array)$ruleNames;

        foreach ($keyRules as $ruleName=>$parameters) {
            if (in_array($ruleName, $ruleNames)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return a custom message (if passed)
     *
     * @param string $key
     * @param string $ruleName
     * @param array  $customMessages
     *
     * @return string
     **/
    protected function getCustomMessage($key, $ruleName, array $customMessages)
    {

        // First check for custom messages with key
        if (isset($customMessages["$key.$ruleName"])) {
            return $customMessages["$key.$ruleName"];
        }

        // Then check for custom messages without key
        if (isset($customMessages[$ruleName])) {
            return $customMessages[$ruleName];
        }

        // Then check for custom translator messages
        $customTranslationKey = "custom.$key.$ruleName";

        if ($this->textProvider->has($customTranslationKey)) {
            return $this->textProvider->get($customTranslationKey);
        }

        return '';

    }
}
