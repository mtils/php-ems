<?php


namespace Ems\Validation\Illuminate;

use Ems\Contracts\Core\TextProvider;
use Ems\Contracts\Validation\Validation;
use Ems\Contracts\Validation\ValidationConverter as ConverterContract;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\MessageBag;


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
     * @param TextProvider $textProvider
     **/
    public function __construct(TextProvider $textProvider)
    {
        $this->textProvider = $textProvider;
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

        if (!in_array($format, [MessageBagContract, MessageBag])) {
            throw new UnsupportedParameterException(static::class . ' only supports MessageBag');
        }

        $messages = new MessageBag;

        if (!count($validation)) {
            return $messages;
        }

        foreach ($validation as $key=>$rules) {
            foreach ($rules as $ruleName=>$parameters) {
                echo "\n" . $this->getMessage($key, $ruleName, $rules, $customMessages);
            //                 $messages->add($key, );
            }
        }

        return $messages;

    }

    protected function getMessage($key, $ruleName, array $keyRules, array $customMessages = [])
    {

        if (in_array($ruleName, $this->sizeRules)) {
            return $this->getSizeMessage($key, $ruleName, $customMessages);
        }

        if ($customMessage = $this->getCustomMessage($key, $ruleName, $customMessages)) {
            return $customMessage;
        }

        return $this->textProvider->get($ruleName);
    }

    protected function getSizeMessage($key, $ruleName, array $keyRules, array $customMessages)
    {
        $type = $this->getRuleType($keyRules);
        return $this->getMessage($key, "$ruleName.$type", $keyRules, $customMessages);
    }

    protected function getRuleType(array $keyRules)
    {
        // We assume that the attributes present in the file array are files so that
        // means that if the attribute does not have a numeric rule and the files
        // list doesn't have it we'll just consider it a string by elimination.
        if ($this->hasRule($keyRules, $this->numericRules)) {
            return 'numeric';
        } elseif ($this->hasRule($keyRules, 'array')) {
            return 'array';
        } elseif ($this->hasRule($keyRules, 'file')) {
            return 'file';
        }

        return 'string';
    }

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
        $customTranslation = $this->textProvider->get($customTranslationKey);

        if ($customTranslationKey != $customTranslation) {
            return $customTranslation;
        }

    }
}
