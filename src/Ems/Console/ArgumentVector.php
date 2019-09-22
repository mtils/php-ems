<?php
/**
 *  * Created by mtils on 22.09.19 at 05:33.
 **/

namespace Ems\Console;


use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\StringableTrait;
use Ems\Core\Exceptions\UnsupportedParameterException;
use function array_shift;
use function explode;
use function implode;
use function mb_strlen;
use function mb_substr;
use function strlen;
use function strpos;
use function substr;

/**
 * Class ArgumentVector
 *
 * @see http://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html
 *
 * The ARGumentVector class is a class that basically pre parses the parameters.
 * The only thing it needs to know is the name for the short parameters and if
 * they take a value.
 * Unlike Symfony this is more a pre parser. It does not exactly match the input
 * to your definitions and let you mix short and long options without complaining.
 *
 * @package Ems\Console
 */
class ArgumentVector implements Stringable
{
    use StringableTrait;

    /**
     * @var array
     */
    protected $argv = [];

    /**
     * @var string
     */
    protected $command = '';

    /**
     * ARGV parts not starting with -
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * ARGV parts starting with --
     *
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $definedShortOptions = [];

    /**
     * @var bool
     */
    protected $removeCommand = true;

    /**
     * @var bool
     */
    private $parsed = false;

    /**
     * ArgumentVector constructor.
     *
     * @param array $argv
     * @param bool  $removeCommand (default:true) remove the first segment of argv
     */
    public function __construct(array $argv, $removeCommand=true)
    {
        $this->argv = $argv;
        $this->removeCommand = $removeCommand;
    }

    /**
     * @return array
     */
    public function argv()
    {
        return $this->argv;
    }

    /**
     * @return string
     */
    public function command()
    {
        $this->parseIfNotParsed();
        return $this->command;
    }

    /**
     * Return all argv segments not starting with -
     *
     * @return string[]
     */
    public function arguments()
    {
        $this->parseIfNotParsed();
        return $this->arguments;
    }

    /**
     * A $key=>$value array of all long options. (starting with --)
     *
     * @return array
     */
    public function options()
    {
        $this->parseIfNotParsed();
        return $this->options;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return implode(' ', $this->argv);
    }

    /**
     * Define a short option. The class will not behave expected with short
     * options unless you tell it the names of em.
     * Short options can have only exactly one char. Tell this class if this
     * option can take a value or is it just a flag.
     *
     * Short options taking values will "eat" the next found argument. Flags
     * don't.
     *
     * @param string $char
     * @param bool   $takesValue (default:false)
     *
     * @return $this
     */
    public function defineShortOption($char, $takesValue=false)
    {

        if (strlen($char) != 1) {
            throw new UnsupportedParameterException("Short options can have only one char (tried to add:'$char')");
        }

        $this->definedShortOptions[$char] = $takesValue;

        return $this;
    }

    /**
     * Get all defined (known) short options.
     *
     * @return array
     */
    public function definedShortOptions()
    {
        return array_keys($this->definedShortOptions);
    }

    /**
     * @param string $option
     *
     * @return bool
     */
    public function isShortOptionDefined($option)
    {
        return isset($this->definedShortOptions[$option]);
    }

    /**
     * Return true if the $option takes a value or is just a flag.
     *
     * @param string $option
     * @return bool
     */
    public function takesValue($option)
    {
        return isset($this->definedShortOptions[$option]) && $this->definedShortOptions[$option];
    }

    /**
     * Parse the arguments and options
     */
    protected function parseIfNotParsed()
    {
        if ($this->parsed) {
            return;
        }

        $argv = $this->argv;

        if ($this->removeCommand) {
            $this->command = array_shift($argv);
        }

        $previousShortOption = '';
        $onlyArgumentsFollowing = false;

        foreach ($argv as $i=>$token) {

            if ($token === '--') {
                $onlyArgumentsFollowing = true;
                continue;
            }

            if ($onlyArgumentsFollowing) {
                $this->arguments[] = $token;
                continue;
            }

            if (strpos($token, '--') === 0) {
                $this->addLongOption($token);
                $previousShortOption = '';
                continue;
            }

            if (strpos($token, '-') === 0) {
                $previousShortOption = $this->addShortOption($token);
                continue;
            }

            if ($previousShortOption && $this->takesValue($previousShortOption)) {
                $this->addToOptions($previousShortOption, $token);
                $previousShortOption = '';
                continue;
            }

            $this->arguments[] = $token;
            $previousShortOption = '';
        }

        $this->parsed = true;

    }

    /**
     * Add a long option and return its name.
     *
     * @param string $token
     *
     * @return string
     */
    protected function addLongOption($token)
    {
        $name = substr($token, 2);
        $equalPos = strpos($name, '=');

        if ($equalPos === false) {
            $this->addToOptions($name, true);
            return $name;
        }

        list($name, $value) = explode('=', $name, 2);

        $this->addToOptions($name, $value);

        return $name;
    }

    /**
     * Add a short option. A short option MUST have only one char, The method
     * return the name of the last added option IFF it didnt contain a value.
     *
     * @param string $token
     *
     * @return string
     */
    protected function addShortOption($token)
    {
        $name = substr($token, 1);
        $length = mb_strlen($name);

        for ($i=0; $i<$length; $i++) {

            $char = $name[$i];

            if (!$this->takesValue($char)) {
                $this->addToOptions($char, true, true);
                continue;
            }

            $remainingValue = mb_substr($name, $i+1);

            // If the remaining value is exactly '' just return it and let the
            // caller add it later when he received the value
            if ($remainingValue === '') {
                return $char;
            }

            $this->addToOptions($char, $remainingValue);

            return '';
        }

        return '';

    }

    /**
     * Adds a value to the options. If a previous value was added make it an array.
     *
     * @param string $name
     * @param mixed $value
     * @param bool  $overwrite (default:false)
     */
    protected function addToOptions($name, $value, $overwrite=false)
    {

        if (!isset($this->options[$name]) || $overwrite) {
            $this->options[$name] = $value;
            return;
        }

        if (!is_array($this->options[$name])) {
            $this->options[$name] = [$this->options[$name]];
        }

        $this->options[$name][] = $value;
    }

}