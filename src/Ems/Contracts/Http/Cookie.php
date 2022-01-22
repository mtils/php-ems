<?php
/**
 *  * Created by mtils on 10.01.2022 at 21:00.
 **/

namespace Ems\Contracts\Http;

use DateTime;
use DateTimeInterface;
use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\StringableTrait;
use OutOfBoundsException;
use UnexpectedValueException;

use function in_array;
use function is_bool;
use function is_numeric;

/**
 * @property string                 name
 * @property string                 value
 * @property DateTimeInterface|null expire
 * @property string                 path
 * @property string                 domain
 * @property bool                   secure
 * @property bool                   httpOnly
 * @property string                 sameSite
 */
class Cookie implements Stringable
{
    use StringableTrait;

    public const NONE = 'none';

    public const LAX = 'lax';

    public const STRICT = 'strict';

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var string
     */
    private $value = '';

    /**
     * @var DateTimeInterface|null
     */
    private $expire;

    /**
     * @var string
     */
    private $path = '/';

    /**
     * @var string
     */
    private $domain = '';

    /**
     * @var bool
     */
    private $secure;

    /**
     * @var bool
     */
    private $httpOnly;

    /**
     * @var string
     */
    private $sameSite = self::LAX;

    /**
     * @var bool
     */
    public static $defaultSecure = true;

    /**
     * @var bool
     */
    public static $defaultHttpOnly = true;

    /**
     * @var string
     */
    public static $defaultSameSite = self::LAX;

    /**
     * @param string                $name
     * @param string                $value
     * @param DateTimeInterface|int $expire Minutes or an end date
     * @param string|null           $path
     * @param string|null           $domain
     * @param bool|null             $secure
     * @param bool|null             $httpOnly
     * @param string|null           $sameSite
     */
    public function __construct(string $name, string $value, $expire = null, string $path=null, string $domain=null, bool $secure = null, bool $httpOnly = null, string $sameSite=null)
    {
        $this->__set('name', $name);
        $this->__set('value', $value);
        $this->__set('expire', $expire);
        $this->__set('path', $path === null ? '/' : $path);
        $this->__set('domain', $domain ?: '');
        $this->__set('secure', $secure === null ? self::$defaultSecure : $secure);
        $this->__set('httpOnly', $httpOnly === null ? self::$defaultHttpOnly : $httpOnly);
        $this->__set('sameSite', $sameSite === null ? self::$defaultSameSite : $sameSite);

    }

    public function __get(string $key)
    {
        switch ($key) {
            case 'name':
                return $this->name;
            case 'value':
                return $this->value;
            case 'expire':
                return $this->expire;
            case 'path':
                return $this->path;
            case 'domain':
                return $this->domain;
            case 'secure':
                return $this->secure;
            case 'httpOnly':
                return $this->httpOnly;
            case 'sameSite':
                return $this->sameSite;
        }
        throw new OutOfBoundsException("Property '$key' does not exist in Cookie");
    }

    public function __set(string $key, $value)
    {
        switch ($key) {
            case 'name':
                $this->name = $value;
                return;
            case 'value':
                $this->value = $value;
                return;
            case 'expire':
                $this->setExpire($value);
                return;
            case 'path':
                $this->path = $value;
                return;
            case 'domain':
                $this->domain = $value;
                return;
            case 'secure':
                $this->secure = $value;
                return;
            case 'httpOnly':
                if (!is_bool($value)) {
                    throw new UnexpectedValueException('httpOnly must be boolean.');
                }
                $this->httpOnly = $value;
                return;
            case 'sameSite':
                if (!in_array($value, [self::NONE, self::LAX, self::STRICT])) {
                    throw new UnexpectedValueException('sameSite has to be none,lax or strict');
                }
                $this->sameSite = $value;
                return;
        }
        throw new OutOfBoundsException("Property '$key' does not exist in Cookie");
    }

    /**
     * Return the value as string for simple array cookie usage.
     *
     * @return string
     */
    public function toString() : string
    {
        return $this->value;
    }


    protected function setExpire($expire)
    {
        if ($expire === null) {
            $this->expire = $expire;
            return;
        }
        if (is_numeric($expire)) {
            $now = new DateTime();
            $expire = $now->modify("+$expire Minutes");
        }
        if (!$expire instanceof DateTime) {
            throw new UnexpectedValueException("Expire has to be DateTime or int minutes");
        }
        $this->expire = $expire;
    }
}