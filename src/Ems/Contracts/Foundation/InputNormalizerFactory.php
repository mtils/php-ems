<?php

namespace Ems\Contracts\Foundation;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Validation\ValidatorFactory;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Core\Extendable;

/**
 * The InputNormalizerFactory is the factory for the InputNormalizer objects.
 * You should categorize the input by its type (http.browser.get, http.browser.post)
 * http.browser.delete, http.api.get, console.linux.argv, http.js.get,...whatever)
 * The segment count has to be 3.
 *
 * Assign a callable which configures the created InputNormalizer for use
 * with the desired adjustments/casts/validator.
 * The extension gets the normalizer, inputType, the resource, the locale.
 * Wildcard segments are allowed. If your callable matches many inputTypes
 * all of em will be called.
 * To overwrite a extension you have to use the same pattern as the assign one.
 *
 * If you want to support a new type just call $factory->extend('http.browser.get', fn(){});
 * If then a matching input normalizer is created, youre callable receives the
 * normalizer and its up to the callable which adjustments, casting, validation
 * should run.
 * Just like this:
 * $factory->extend('http.*.post', function ($normalizer, $inputType, $resource, $locale) {
 *     $normalizer->adjust('to_null|no_method|remove_token|xtype_adjust')
 *                ->validate(true)
 *                ->cast('to_nested|xtype_cast');
 * });
 *
 * Then when using the normalizer in a controller, request, import or so just
 * call $factory->normalizer('http.browser.post')->normalize($input)
 * and all the globally assigned processors will process the input.
 *
 * If you use InputNormalizerFactory::onBefore('adjust') (or validate or cast)
 * your listener will be copied to every created normalizer.
 *
 * If you want to have your listener only in one instance of a normalizer (this
 * is mostly the case) you should hook into the normalizer:
 * InputNormalizerFactory::normalizer('http.api.get')->onAfter('adjust', fn(){});
 *
 * The pattern wildcards are processed by priority. Higher priority extensions
 * are called later (cause they have the last word).
 * example:
 * http.browser.get -> *.*.*
 *                  -> http.*.*
 *                  -> *.browser.*
 *                  -> *.*.get
 *                  -> http.browser.*
 *                  -> http.*.get
 *                  -> *.browser.get
 *                  -> http.browser.get
 *
 * Basic proposals for often used input types are:
 *
 * http.browser.$method
 * http.js.$method
 * http.json-api.$method
 * file.import.csv
 * console.bash.argv
 * console.cron.argv
 * console.windows.pipe
 * 
 **/
interface InputNormalizerFactory extends Extendable, HasMethodHooks
{

    /**
     * Return a matching InputNormalizer for $inputType and optionally $resource
     *
     * @param string                   $inputType
     * @param string|AppliesToResource $resource (optional)
     * @param string                   $locale
     *
     * @return array
     **/
    public function normalizer($inputType, $resource=null, $locale=null);

    /**
     * Return the default adjust input processor. It is mainly used to add your
     * globally available adjusters via InputNormalizerFactory::adjuster()->extend()
     * 
     * @return InputProcessor
     **/
    public function adjuster();

    /**
     * Return the ValidatorFactory used by the InputNormalizerFactory. This is
     * just to have access to all dependencies.
     *
     * @return ValidatorFactory
     **/
    public function validatorFactory();

    /**
     * Return the default cast input processor. It is mainly used to add your
     * globally available casters via InputNormalizerFactory::caster()->extend()
     *
     * @return InputProcessor
     **/
    public function caster();

}
