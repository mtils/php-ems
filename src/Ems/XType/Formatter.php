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
use Ems\Contracts\Core\PointInTime;
use Ems\Contracts\Core\TextProvider;
use Ems\Contracts\XType\Formatter as FormatterContract;
use Ems\Contracts\XType\XType;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Helper;
use Ems\Core\Patterns\ExtendableByClassHierarchyTrait;
use Ems\Contracts\XType\TypeProvider as TypeProviderContract;
use function in_array;
use function str_replace;

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
     * @var TextProvider
     */
    protected $textProvider;

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

    /**
     * @var string
     */
    protected $trueLangKey = 'true';

    /**
     * @var string
     */
    protected $falseLangKey = 'false';

    /**
     * @var array
     */
    protected $viewToVerbosity = [
        'default' => CoreFormatter::SHORT,
        'show'    => CoreFormatter::LONG,
        'index'   => CoreFormatter::SHORT,
        'detail'  => CoreFormatter::VERBOSE,
        'edit'    => CoreFormatter::VERBOSE
    ];

    public function __construct(CoreFormatter $formatter, Extractor $extractor,
                                TypeProviderContract $typeProvider,
                                TextProvider $textProvider, array $extensions=[])
    {
        $this->coreFormatter = $formatter;
        $this->extractor = $extractor;
        $this->typeProvider = $typeProvider;
        $this->textProvider = $textProvider;
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
        return call_user_func($formatter, $type, $value, $view, $this->locale);
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
            $this->textProvider,
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
        if ($this->textProvider instanceof Multilingual) {
            $this->textProvider->setLocale($locale);
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
        if ($this->textProvider instanceof Multilingual) {
            $this->textProvider->setFallbacks($this->localeFallbacks);
        }
        return $this;
    }

    /**
     * Manually map a view name to a Core\Formatter verbosity.
     * Use this to choose a distinct (date) format for a given view.
     *
     * @param string $view
     * @param string $verbosity
     *
     * @return $this
     */
    public function mapViewToVerbosity($view, $verbosity)
    {
        if (!in_array($verbosity, [CoreFormatter::SHORT, CoreFormatter::LONG, CoreFormatter::VERBOSE])) {
            throw new UnsupportedParameterException("Verbosity $verbosity is not known.");
        }
        $this->viewToVerbosity[$view] = $verbosity;
        return $this;
    }

    /**
     * Set the translation key for turning boolean true values into text.
     *
     * @return string
     */
    public function getTrueLangKey()
    {
        return $this->trueLangKey;
    }

    /**
     * Set the translation key for turning boolean true values into text
     *
     * @param string $trueLangKey
     * @return Formatter
     */
    public function setTrueLangKey($trueLangKey)
    {
        $this->trueLangKey = $trueLangKey;
        return $this;
    }

    /**
     * Set the translation key for turning boolean false values into text
     *
     * @return string
     */
    public function getFalseLangKey()
    {
        return $this->falseLangKey;
    }

    /**
     * Set the translation key for turning boolean false values into text
     *
     * @param string $falseLangKey
     * @return Formatter
     */
    public function setFalseLangKey($falseLangKey)
    {
        $this->falseLangKey = $falseLangKey;
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

        if ($type instanceof UnitType) {
            return $this->formatUnit($type, $value, $view, $lang);
        }

        if ($type instanceof NumberType) {
            return $this->formatNumber($type, $value, $view, $lang);
        }

        if ($type instanceof StringType) {
            return $this->formatString($type, $value, $view, $lang);
        }

        if ($type instanceof TemporalType) {
            return $this->formatTemporal($type, $value, $view, $lang);
        }

        return 'NaN';
    }

    protected function formatBool(BoolType $type, $value, $view, $lang)
    {
        $langKey = $value ? $this->trueLangKey : $this->falseLangKey;
        return $this->textProvider->get($langKey);
    }

    protected function formatNumber(NumberType $type, $value, $view, $lang)
    {
        return $this->coreFormatter->number($value, $type->decimalPlaces);
    }

    protected function formatString(StringType $type, $value, $view, $lang)
    {
        return $value;
    }

    protected function formatUnit(UnitType $type, $value, $view, $lang)
    {
        return $this->coreFormatter->unit($value, $type->unit, $type->decimalPlaces);
    }

    protected function formatTemporal(TemporalType $type, $value, $view, $lang)
    {

        $verbosity = $this->viewToVerbosity[$view];

        if (!$type->absolute) {
            return $this->formatRelativeTemporal($type, $value, $verbosity);
        }

        if (in_array($type->precision, [PointInTime::HOUR, PointInTime::MINUTE, PointInTime::SECOND])) {
            return $this->coreFormatter->dateTime($value, $verbosity);
        }

        return $this->coreFormatter->date($value, $verbosity);

    }

    protected function formatRelativeTemporal(TemporalType $type, $value, $view)
    {
        if ($type->precision == PointInTime::MONTH) {
            return \Ems\Core\PointInTime::guessFrom($value)->format('F');
        }

        if ($type->precision == PointInTime::WEEKDAY) {
            return \Ems\Core\PointInTime::guessFrom($value)->format('l');
        }

        // month + day
        if ($type->precision == PointInTime::DAY) {

            $format = $this->coreFormatter->getFormat(CoreFormatter::DATE);
            $format = str_replace(['L', 'o', 'Y', 'y'],'', $format); // Remove all year output
            $format = trim($format, '.-,_/');

            return \Ems\Core\PointInTime::guessFrom($value)->format($format);
        }

        $format = $this->coreFormatter->getFormat(CoreFormatter::TIME);

        // $hour
        if ($type->precision == PointInTime::HOUR) {
            // We dont need leading zeros, just make sure to stay in 12/24h format
            $hourFormat = Helper::contains($format, ['g', 'h']) ? 'g' : 'G';
            return \Ems\Core\PointInTime::guessFrom($value)->format($hourFormat);
        }

        // $hour:$minute
        if ($type->precision == PointInTime::MINUTE) {
            $format = str_replace(['s', 'v', 'u'],'', $format); // Remove all second output
            $format = trim($format, '.-,_:');

            return \Ems\Core\PointInTime::guessFrom($value)->format($format);
        }

        // $hour:$minute:$second
        return \Ems\Core\PointInTime::guessFrom($value)->format($format);

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

        if ($extension = $this->nearestForClass($class)) {
            $this->formatterCache[$name] = $extension;
            $this->formatterCache[$class] = $extension;
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