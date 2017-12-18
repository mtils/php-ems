<?php

namespace Ems\Foundation;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Foundation\InputNormalizer as InputNormalizerContract;

trait SelfNormalizingTrait
{

    /**
     * @var array
     **/
    protected $_methodCache = [];

    /**
     * Add the class using this trait to the passed normalizer
     *
     * @param InputNormalizerContract $normalizer
     **/
    protected function addMeToNormalizer(InputNormalizerContract $normalizer)
    {
        foreach (['adjust', 'validate', 'cast'] as $action) {

            $normalizer->onAfter($action, function ($input, $resource, $locale) use ($action) {
                return $this->selfNormalize($action, $input, $resource, $locale);
            });

        }
    }

    /**
     * Perform the actual processing. The trait searches for adjust$key methods (in camelCase)
     * validate$key and cast$key.
     *
     * @param string            $action
     * @param array             $input
     * @param AppliesToResource $resource (optional)
     * @param string            $locale (optional)
     *
     * @return array
     **/
    protected function selfNormalize($action, array $input, AppliesToResource $resource=null, $locale=null)
    {

        foreach ($input as $key=>$value) {

            if (!$method = $this->keyToNormalizeMethod($key, $action)) {
                continue;
            }

            if ($action == 'validate') {
                $this->{$method}($value, $input, $resource, $locale);
                continue;
            }


            $input[$key] = $this->{$method}($value, $input, $resource, $locale);


        }

        return $input;
    }

    /**
     * Create a method name for a hook on a key
     *
     * @param string $key
     * @param string $prefix
     * @return string
     **/
    protected function keyToNormalizeMethod($key, $prefix)
    {
        $arrayKey = "$prefix:$key";

        if (!isset($this->_methodCache[$arrayKey])) {

            $method = $prefix . ucfirst(Type::camelCase(str_replace([' ', '.'],'_', $key)));

            $this->_methodCache[$arrayKey] = method_exists($this, $method) ? $method : false;

        }

        return $this->_methodCache[$arrayKey];

    }
}
