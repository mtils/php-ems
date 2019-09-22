<?php
/**
 *  * Created by mtils on 14.09.19 at 17:36.
 **/

namespace Ems\Contracts\Routing;


use Ems\Contracts\Core\Arrayable;
use Ems\Contracts\Core\Map;
use Ems\Core\Helper;
use Ems\Core\Support\ObjectReadAccess;
use function explode;
use function is_array;
use function substr;
use function trim;

/**
 * Class Command
 *
 * A command is the console counterpart to a route. In EMS you define your
 * console commands like routes. The whole definition then lies in you "console
 * route" and you can use everything in your application as a command. The same
 * as with routes.
 *
 * Like in Ems\Contracts\Core\Url: Properties are for read access, methods for
 * write access.
 *
 * @property-read string     pattern The pattern/uri/name like import:run or migrate:status
 * @property-read mixed      handler The assigned (whatever) handler
 * @property-read Argument[] arguments The console arguments (./console config:get $argument1 $argument2)
 * @property-read Option[]   options The console options (./console assets:copy --option1 --option2=value
 * @property-read string     description
 *
 * @package Ems\Contracts\Routing
 */
class Command implements Arrayable
{
    use ObjectReadAccess;

    /**
     * @var array
     */
    protected $_properties = [
        'pattern'      => '',
        'arguments'   => [],
        'options'   => [],
        'description'  => '',
    ];

    /**
     * Command constructor.
     *
     * @param string $pattern
     * @param string $description
     */
    public function __construct($pattern, $description='')
    {
        $this->setPattern($pattern);
        $this->description($description);

    }

    /**
     * Register an input argument. Repeat calls for multiple arguments.
     *
     * @param string|Argument|array $signature
     * @param string $description (optional)
     *
     * @return $this
     *
     * @example ./console import:run $file
     *
     * ->argument('file') // required argument
     * ->argument('file=/dev/null') // argument with default
     * ->argument('file?') // optional argument
     * ->argument('?file') // optional argument
     *
     */
    public function argument($signature, $description='')
    {
        // Many arguments at once?
        if (is_array($signature)) {
            Map::apply($signature, [$this, 'argument']);
            return $this;
        }

        if (!$signature instanceof Argument) {
            $signature = $this->parseArgumentSignature($signature);
            $signature->description = $description;
        }

        $this->_properties['arguments'][] = $signature;

        return $this;
    }

    /**
     * Register an input option. Repeat calls for multiple options.
     *
     * @param string|Option|array $signature
     * @param string $description (optional)
     * @param string $shortcut (optional)
     *
     * @return $this
     *
     * @example ./console queue:work
     *
     * ->option('silent', 'Makes it very silently', 's') // bool type, optional. Shortcut is "s".
     * ->option("retry=") //  value without default
     * ->option("retry=5") // value + default
     * ->option("!retry=5") // required option (in opposite to the naming "option"
     *
     */
    public function option($signature, $description='', $shortcut='')
    {
        // Many arguments at once?
        if (is_array($signature)) {
            Map::apply($signature, [$this, 'option']);
            return $this;
        }

        if (!$signature instanceof Option) {
            $signature = $this->parseOptionSignature($signature);
            $signature->description = $description;
            $signature->shortcut = $shortcut;
        }

        $this->_properties['options'][] = $signature;
        return $this;
    }

    /**
     * Set a description for the command.
     *
     * @param string $description
     *
     * @return $this
     */
    public function description($description)
    {
        $this->_properties['description'] = $description;
        return $this;
    }

    /**
     * Set the pattern/uri/name.
     * @example autoload:dump
     *
     * @param string $pattern
     *
     * @return $this
     */
    public function setPattern($pattern)
    {
        $this->_properties['pattern'] = $pattern;
        return $this;
    }

    /**
     * This is a performance related method. In this method
     * you should implement the fastest was to get every
     * key and value as an array.
     * Only the root has to be an array, it should not build
     * the array by recursion.
     *
     * @return array
     **/
    public function toArray()
    {
        return $this->_properties;
    }

    /**
     * @param string $signature
     *
     * @return Argument
     */
    protected function parseArgumentSignature($signature)
    {
        $isOptional = false;
        $default = null;
        $type = null;

        // If starts or ends with ? its optional
        if (Helper::startsWith($signature, '?') || Helper::endsWith($signature,'?')) {
            $isOptional = true;
            $signature = trim($signature, '?');
        }

        $parts = explode('=', $signature);
        $name = $parts[0];
        $default = isset($parts[1]) ? $parts[1] : null;

        // If the default value contained a space we assume it is an array
        if ($default && Helper::contains(trim($default), ' ')) {
            $type = 'array';
            $default = explode(' ', $default);
        }

        if (Helper::endsWith($name, '*')) {
            $name = substr($name, 0, -1);
            $type = 'array';
        }

        return (new Argument())->fill([
            'name'      => $name,
            'required'  => !$isOptional,
            'type'      => $type ?: 'string',
            'default'   => $default
        ]);
    }

    /**
     * @param string $signature
     *
     * @return Option
     */
    protected function parseOptionSignature($signature)
    {
        $isRequired = false;
        $default = null;
        $type = null;

        // If starts with ! it is required
        if (Helper::startsWith($signature, '!')) {
            $isRequired = true;
            $signature = substr($signature, 1);
        }

        $parts = explode('=', $signature);
        $name = $parts[0];
        $default = isset($parts[1]) ? $parts[1] : null;

        $type = $default === null ? 'bool' : null;

        if ($default == '*' || $default == '[]') {
            $type = 'array';
            $default = [];
        }

        return (new Option())->fill([
            'name'      => $name,
            'required'  => $isRequired,
            'type'      => $type ?: 'string',
            'default'   => $default
        ]);
    }
}