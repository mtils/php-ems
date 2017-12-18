<?php
/**
 *  * Created by mtils on 29.11.17 at 20:25.
 **/

namespace Ems\XType;

use function call_user_func;
use Closure;
use Ems\Contracts\Core\Extractor;
use Ems\Contracts\Core\Formatter as CoreFormatter;
use Ems\Contracts\Core\Multilingual;
use Ems\Contracts\XType\Formatter as FormatterContract;
use Ems\Contracts\XType\XType;
use Ems\Core\Patterns\ExtendableByClassHierarchyTrait;
use Ems\Contracts\XType\TypeProvider as TypeProviderContract;

class Formatter implements FormatterContract, Multilingual
{
    use ExtendableByClassHierarchyTrait;

    /**
     * @var CoreFormatter
     */
    protected $coreFormatter;

    /**
     * @var Extractor
     */
    protected $extractor;

    /**
     * @var TypeProviderContract
     */
    protected $typeProvider;

    /**
     * @var array
     */
    protected $formatterCache = [];

    /**
     * @var Closure
     */
    protected $ownExtension;

    /**
     * @var string
     */
    protected $locale = '';

    /**
     * @var array
     */
    protected $localeFallbacks = [];

    public function __construct(CoreFormatter $formatter, Extractor $extractor,
                                TypeProviderContract $typeProvider, array $extensions=[])
    {
        $this->coreFormatter = $formatter;
        $this->extractor = $extractor;
        $this->typeProvider = $typeProvider;
        $this->_extensions = $extensions;
        $this->createOwnExtension();
    }

    /**
     * @inheritdoc
     *
     * @param object $object
     * @param string $path
     * @param string $view
     *
     * @return string|null
     */
    public function format($object, $path, $view = 'default')
    {
        $type = $this->typeProvider->xType($object, $path);
        $value = $this->extractor->value($object, $path);
        return $this->value($type, $value, $view);
    }

    /**
     * @inheritdoc
     *
     * @param XType $type
     * @param mixed $value
     * @param string $view (default:'default')
     *
     * @return string|null
     */
    public function value(XType $type, $value, $view = 'default')
    {
        $formatter = $this->getFormatter($type);
        return call_user_func($formatter, $type, $value, $view);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $locale
     * @param string|array $fallbacks (optional)
     *
     * @return self
     **/
    public function forLocale($locale, $fallbacks = null)
    {
        $fork = new static(
            $this->coreFormatter,
            $this->extractor,
            $this->typeProvider,
            $this->_extensions
        );

        if ($fallbacks) {
            $fork->setFallbacks((array)$fallbacks);
        }

        return $fork->setLocale($locale);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $locale
     *
     * @return self
     **/
    public function setLocale($locale)
    {
        $this->locale = $locale;
        if ($this->coreFormatter instanceof Multilingual) {
            $this->coreFormatter->setLocale($locale);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getFallbacks()
    {
        return $this->localeFallbacks;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $fallback
     *
     * @return $this
     */
    public function setFallbacks($fallback)
    {
        $this->localeFallbacks = (array)$fallback;
        if ($this->coreFormatter instanceof Multilingual) {
            $this->coreFormatter->setFallbacks($this->localeFallbacks);
        }
        return $this;
    }


    /**
     * Do the formatting by this class.
     *
     * @param XType $type
     * @param $value
     * @param $view
     * @param $lang
     *
     * @return string
     */
    protected function selfFormat(XType $type, $value, $view, $lang)
    {

        if ($type instanceof BoolType) {
            return $this->formatBool($type, $value, $view, $lang);
        }

        if ($type instanceof NumberType) {
            return $this->formatNumber($type, $value, $view, $lang);
        }

        if ($type instanceof StringType) {
            return $this->formatString($type, $value, $view, $lang);
        }

        if ($type instanceof UnitType) {
            return $this->formatUnit($type, $value, $view, $lang);
        }

        if ($type instanceof TemporalType) {
            return $this->formatTemporal($type, $value, $view, $lang);
        }

        return 'NaN';
    }

    protected function formatBool(BoolType $type, $value, $view, $lang)
    {
        return $value ? '1' : '0';
    }

    protected function formatNumber(NumberType $type, $value, $view, $lang)
    {
        return $value ? '1' : '0';
    }

    protected function formatString(StringType $type, $value, $view, $lang)
    {
        return $value ? '1' : '0';
    }

    protected function formatUnit(UnitType $type, $value, $view, $lang)
    {
        return $value ? '1' : '0';
    }

    protected function formatTemporal(TemporalType $type, $value, $view, $lang)
    {
        return $value ? '1' : '0';
    }

    /**
     * Get the right formatter from extensions or itself.
     *
     * @param XType $type
     *
     * @return callable
     */
    protected function getFormatter(XType $type)
    {
        $name = $type->getName();

        if (isset($this->formatterCache[$name])) {
            return $this->formatterCache[$name];
        }

        $class = get_class($type);

        if (isset($this->formatterCache[$class])) {
            return $this->formatterCache[$class];
        }

        if (isset($this->_extensions[$name])) {
            $this->formatterCache[$name] = $this->_extensions[$name];
            return $this->formatterCache[$name];
        }

        if ($extension = $this->getExtension($class)) {
            $this->formatterCache[$name] = $extension;
            return $this->formatterCache[$name];
        }

        return $this->ownExtension;
    }

    /**
     * Creates the callable which does the own formatting.
     */
    protected function createOwnExtension()
    {
        $this->ownExtension = function (XType $type, $value, $view, $lang) {
            return $this->selfFormat($type, $value, $view, $lang);
        };
    }
}