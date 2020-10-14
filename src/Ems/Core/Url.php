<?php

namespace Ems\Core;

use ArrayIterator;
use Ems\Contracts\Core\StringableTrait;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Collections\StringList;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use function func_get_args;
use function http_build_query;
use function is_array;
use function parse_str;
use function rawurldecode;
use function rawurlencode;

/**
 * Class Url
 *
 * This is the base url implementation. Now it also implement psr
 * URIInterface.
 * Please do either depend on the psr interface OR the ems interface
 * but not both! Perhaps I will remove the psr interface some day...
 * The ems interface is basically properties for getting values, methods
 * for setting values. All with the shortest syntax I could plan.
 * PSR is something different.
 *
 * @package Ems\Core
 *
 * @property string     $scheme   The scheme (or protocol)
 * @property string     $user     The user part of authority
 * @property string     $password The password part of authority
 * @property string     $host     The host (or domain) name
 * @property int        $port     The tcp port
 * @property StringList $path     The path component
 * @property array      $query    The query parameters
 * @property string     $fragment The last part behind #
 **/
class Url implements UrlContract, UriInterface
{
    use StringableTrait;

    /**
     * An array of all flat schemes.
     *
     * @var array
     **/
    public static $flatSchemes = [
        'mailto' => true,
        'news'   => true,
        'nntp'   => true,
        'telnet' => true,
        'console'=> true
    ];

    /**
     * @var string
     **/
    protected $scheme = '';

    /**
     * @var string
     **/
    protected $user = '';

    /**
     * @var string
     **/
    protected $password = '';

    /**
     * @var string
     **/
    protected $host = '';

    /**
     * @var int
     **/
    protected $port = 0;

    /**
     * @var StringList
     **/
    protected $path;

    /**
     * @var array
     **/
    protected $query = [];

    /**
     * @var string
     **/
    protected $fragment = '';

    /**
     * @var array
     **/
    protected $properties = [
        'scheme'   => '',
        'user'     => '',
        'password' => '',
        'host'     => '',
        'port'     => 0,
        'path'     => [],
        'query'    => [],
        'fragment' => '',
    ];

    /**
     * @var string|null
     */
    protected $toStringCache;

    /**
     * @param string|self|array $url
     **/
    public function __construct($url = null)
    {
        $this->path = new StringList([], '/');

        if ($url) {
            $this->fill($url);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string $scheme (optional)
     *
     * @return self
     **/
    public function scheme($scheme)
    {
        return $this->replicate(['scheme' => $scheme]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $user
     *
     * @return self
     **/
    public function user($user)
    {
        return $this->replicate(['user' => $user]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $password
     *
     * @return self
     **/
    public function password($password)
    {
        return $this->replicate(['password' => $password]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $host
     * @retrurn self
     **/
    public function host($host)
    {
        return $this->replicate(['host' => $host]);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $port
     *
     * @return self
     **/
    public function port($port)
    {
        return $this->replicate(['port' => $port]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $path
     *
     * @return self
     **/
    public function path($path)
    {
        return $this->replicate(['path' => $path]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $segment
     *
     * @return self
     **/
    public function append($segment)
    {
        $segments = is_array($segment) ? $segment : func_get_args();

        return $this->replicate(['path' => $this->path->copy()->extend($segments)]);
    }

    /**
     * Prepend a segment to the path.
     *
     * @param string|array $segment
     *
     * @return self
     **/
    public function prepend($segment)
    {
        $segments = is_array($segment) ? $segment : func_get_args();

        $newPath = $this->path->copy();

        foreach (array_reverse($segments) as $segment) {
            $newPath->prepend($segment);
        }

        return $this->replicate(['path' => $newPath]);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $count
     *
     * @return self
     **/
    public function pop($count = 1)
    {
        $newPath = $this->path->copy();
        for ($i = 0; $i < $count; ++$i) {
            $newPath->pop();
        }

        return $this->replicate(['path' => $newPath]);
    }

    /**
     * {@inheritDoc}
     *
     * @param int $count (default: 1)
     *
     * @return self
     */
    public function shift($count = 1)
    {
        $newPath = $this->path->copy();
        for ($i = 0; $i < $count; ++$i) {
            $newPath->pop(0);
        }
        return $this->replicate(['path' => $newPath]);
    }


    /**
     * {@inheritdoc}
     *
     * @param string|array $key
     * @param mixed        $value (optional)
     *
     * @return self
     **/
    public function query($key, $value = null)
    {
        if ($value) {
            return $this->replicate(['query' => [$key => $value]]);
        }

        return $this->replicate(['query' => $key]);
    }


    /**
     * {@inheritdoc}
     *
     * @param string|array $key
     *
     * @return self
     */
    public function without($key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        $query = $this->query;

        foreach ($keys as $key) {
            if (isset($query[$key])) {
                unset($query[$key]);
            }
        }
        return $this->replicate(['query' => ''])->query($query);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $fragment
     *
     * @return self
     **/
    public function fragment($fragment)
    {
        return $this->replicate(['fragment' => $fragment]);
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function isRelative()
    {
        return !$this->scheme && !$this->path->getPrefix() && !$this->host;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function isEmpty()
    {
        foreach ($this->properties as $property => $default) {
            if ($property == 'path') {
                if (count($this->path)) {
                    return false;
                }
                continue;
            }

            if ($this->$property) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param $part
     *
     * @return mixed
     **/
    public function __get($part)
    {
        if (!isset($this->properties[$part])) {
            throw new InvalidArgumentException("Property $part is unknown");
        }

        // Stay immutable
        if ($part == 'path') {
            return $this->path->copy();
        }

        return $this->$part;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function toString()
    {
        if ($this->toStringCache !== null) {
            return $this->toStringCache;
        }
        $this->toStringCache = '';
        $isFlat = $this->isFlat();

        if ($this->scheme) {
            $slashes = $isFlat ? '' : '//';
            $this->toStringCache .= "{$this->scheme}:$slashes";
        }

        if ($authority = $this->getAuthority()) {
            $this->toStringCache .= $authority;
        }

        if ($this->path->count()) {
            $prefix = !$isFlat && ($authority || $this->path->getPrefix()) ? '/' : '';
            $this->toStringCache .= $prefix.ltrim((string) $this->path, '/');
        }

        if ($this->query) {
            $this->toStringCache .= '?'.http_build_query($this->query);
        }

        if ($this->fragment) {
            $this->toStringCache .= "#{$this->fragment}";
        }

        return $this->toStringCache;
    }

    /**
     * Check if $offset exists.
     *
     * @param mixed $offset
     *
     * @return bool
     **/
    public function offsetExists($offset)
    {
        return isset($this->query[$offset]);
    }

    /**
     * Get value of $offset.
     *
     * @param mixed $offset
     *
     * @return mixed
     **/
    public function offsetGet($offset)
    {
        return $this->query[$offset];
    }

    /**
     * Set the value of $offset.
     *
     * @param mixed $offset
     * @param mixed $value
     **/
    public function offsetSet($offset, $value)
    {
        throw new RuntimeException('An url is immutable, you can only get its query values');
    }

    /**
     * Unset $offset.
     *
     * @param mixed $offset
     **/
    public function offsetUnset($offset)
    {
        return $this->offsetSet($offset, 'foo');
    }

    /**
     * Return the count of this array like object.
     *
     * @return ArrayIterator
     **/
    public function getIterator()
    {
        return new ArrayIterator($this->query);
    }

    /**
     * @inheritdoc
     *
     * @param string|Url   $other
     * @param string|array $parts
     *
     * @return bool
     */
    public function equals($other, $parts=['scheme', 'user', 'host', 'path'])
    {
        $other = $other instanceof UrlContract ? $other : new static($other);
        $parts = (array)$parts;

        foreach ($parts as $part) {
            if ($part == 'path') {
                continue;
            }
            if ($this->$part != $other->$part) {
                return false;
            }
        }

        if (!in_array('path', $parts)) {
            return true;
        }

        return $this->path->equals($other->path);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'scheme'   => $this->scheme,
            'user'     => $this->user,
            'password' => $this->password,
            'host'     => $this->host,
            'port'     => $this->port,
            'path'     => $this->path->copy(),
            'query'    => $this->query,
            'fragment' => $this->fragment,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @return self
     **/
    public function replicate(array $attributes = [])
    {
        if (!isset($attributes['query'])) {
            return new static(array_merge($this->toArray(), $attributes));
        }

        $queryAttributes = $attributes['query'];
        unset($attributes['query']);

        $newAttributes = array_merge($this->toArray(), $attributes);

        $newAttributes['query'] = $this->mergeOrReplaceQuery($queryAttributes);

        return new static($newAttributes);
    }

    /**
     * {@inheritDoc}
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme()
    {
        return $this->__get('scheme');
    }


    /**
     *        ----------authority------------
     *        |                             |
     * http://mike:password@somewhere.net:80/demo/example.php?foo=bar#first
     * |      |    |        |             | |                 |       |
     * |      |    |        host          | url-path          |       fragment
     * |      user password               port                query
     * scheme.
     *
     * @return string
     */
    public function getAuthority()
    {
        if (!$this->host) {
            return '';
        }

        $authority = '';

        if ($userInfo = $this->getUserInfo()) {
            $authority .= "$userInfo@";
        }

        $authority .= $this->host;

        if ($this->port) {
            $authority .= ":$this->port";
        }

        return $authority;
    }

    /**
     * @see MDF_Url::setUserInfo($userinfo)
     *
     * @return string
     */
    public function getUserInfo()
    {
        if (!$userinfo = $this->user) {
            return '';
        }

        $userinfo = rawurlencode($userinfo);

        // Mask password, so that in __toString an other places no passwords
        // will be exposed to the outside.
        // If you need the password, you have to explicit call $url->password.
        if ($this->password) {
            $userinfo .= ":xxxxxx";
        }

        return $userinfo;
    }

    /**
     * {@inheritDoc}
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost()
    {
        return $this->__get('host');
    }

    /**
     * {@inheritDoc}
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        $port = $this->__get('port');
        return $port ? $port : null;
    }

    /**
     * {@inheritDoc}
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath()
    {
        return (string)$this->__get('path');
    }

    /**
     * {@inheritDoc}
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery()
    {
        if (!$this->query) {
            return '';
        }
        return http_build_query($this->query);
    }

    /**
     * {@inheritDoc}
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return $this->__get('fragment');
    }

    /**
     * {@inheritDoc}
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return static A new instance with the specified scheme.
     * @throws InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme)
    {
        return $this->scheme($scheme);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return static A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null)
    {
        $copy = $this->user($user);
        if (!$password) {
            return $copy;
        }
        return $copy->password($password);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $host The hostname to use with the new instance.
     * @return static A new instance with the specified host.
     * @throws InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        return $this->host($host);
    }

    /**
     * {@inheritDoc}
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * @return static A new instance with the specified port.
     * @throws InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
        return $this->port($port === null ? 0 : $port);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $path The path to use with the new instance.
     * @return static A new instance with the specified path.
     * @throws InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        return $this->path($path);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $query The query string to use with the new instance.
     * @return static A new instance with the specified query string.
     * @throws InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        $queryArray = [];
        parse_str($query, $queryArray);
        return $this->query($queryArray);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $fragment The fragment to use with the new instance.
     * @return static A new instance with the specified fragment.
     */
    public function withFragment($fragment)
    {
        return $this->fragment($fragment);
    }

    /**
     * {@inheritdoc}
     *
     * @param * @param string|array|self $url $url
     *
     * @return self
     **/
    protected function fill($url)
    {
        $parts = $this->castToArray($url);

        if (isset($parts['scheme']) && $parts['scheme']) {
            $this->scheme = mb_strtolower($parts['scheme']);
        }

        if (isset($parts['user']) && $parts['user']) {
            $this->user = $parts['user'];
        }

        if (isset($parts['password']) && $parts['password']) {
            $this->password = $parts['password'];
        }

        if (isset($parts['host']) && $parts['host']) {
            $this->host = $parts['host'];
        }

        if (isset($parts['port']) && $parts['port']) {
            $this->port = $parts['port'];
        }

        if (isset($parts['path'])) {
            $this->setPath($parts['path']);
            // See https://github.com/mtils/php-ems/issues/15
            // On hosts with a trailing slash the slash is the
            // path prefix not suffix
            if ($parts['path'] == '/' && $this->host) {
                $this->path->setSuffix('');
            }
        }

        if (isset($parts['query']) && $parts['query']) {
            $this->setQuery($parts['query']);
        }

        if (isset($parts['fragment']) && $parts['fragment']) {
            $this->fragment = $parts['fragment'];
        }

        return $this;
    }

    /**
     * Returns if this is a flat url.
     * mailto: and news: are flat f.e.
     *
     * @see self::$flatSchemes
     *
     * @return bool
     */
    protected function isFlat()
    {
        if (!$this->scheme) {
            return false;
        }
        return static::isFlatScheme($this->scheme);
    }

    /**
     * Casts the passed $url to an array.
     *
     * @param mixed $url
     *
     * @return array
     **/
    protected function castToArray($url)
    {
        if (is_array($url)) {
            return $url;
        }

        if (is_string($url)) {
            return $this->parseUrl($url);
        }

        if (!$url instanceof UrlContract) {
            $typeName = is_object($url) ? get_class($url) : gettype($url);
            throw new InvalidArgumentException("Now idea how to get the values of the passed url $typeName");
        }

        $array = [];

        foreach ($this->properties as $key => $default) {
            $array[$key] = $url->__get($key);
        }

        // If the url had an absolute path but no scheme we have to copy it
        if (isset($array['path'])) {
            $array['path'] = (!$url->isRelative() && !$url->scheme) ? '/'.$array['path'] : $array['path'];
        }

        return $array;
    }

    /**
     * Set the path inline.
     *
     * @param mixed $path
     *
     * @return self
     **/
    protected function setPath($path)
    {
        if ($path instanceof StringList) {
            $this->path = $path;

            return $this;
        }

        if ($path === '') {
            $this->path->setPrefix('')->setSource([]);
            return $this;
        }

        $hasLeadingSlash = ($path[0] == '/');
        $hasTrailingSlash = (substr($path, -1) == '/');

        $path = is_array($path) ? $path : explode('/', trim($path, '/ '));

        $this->path->setSource($path);

        $prefix = $hasLeadingSlash || ($this->scheme && !static::isFlatScheme($this->scheme)) ? '/' : '';
        $suffix = $hasTrailingSlash ? '/' : '';

        $this->path->setPrefix($prefix);
        $this->path->setSuffix($suffix);

        return $this;
    }

    /**
     * Set the inline query.
     *
     * @param string|array $query
     *
     * @return self
     **/
    protected function setQuery($query)
    {
        $this->query = $this->mergeOrReplaceQuery($query);

        return $this;
    }

    /**
     * Merges a new query or replace the current one if the query is a string
     * and starts with ?
     *
     * @param string|array $query
     *
     * @return array
     **/
    protected function mergeOrReplaceQuery($query)
    {
        if (is_array($query)) {
            return array_merge($this->query, $query);
        }

        if (!is_string($query)) {
            throw new InvalidArgumentException('Query has to be array or string not '.gettype($query));
        }

        if ($query === '') {
            return [];
        }

        $normalized = ltrim($query, '?');

        $items = [];
        parse_str($normalized, $items);

        // If the query starts with a ? overwrite the complete url
        if ($query[0] == '?') {
            return $items;
        }

        return array_merge($this->query, $items);
    }

    /**
     * Parses the url.
     *
     * @param string $url
     *
     * @return array
     **/
    protected function parseUrl($url)
    {
        if ($parsed = parse_url($url)) {
            return $this->addCredentials($parsed);
        }

        // A second try for not file:// schemes which does support absolute
        // paths (like sqlite:///)

        // Cut the scheme by hand
        $parts = explode(':', $url, 2);

        if (count($parts) != 2 || strlen($parts[0]) > 24) {
            throw new InvalidArgumentException("Unparseable url $url");
        }

        // Now cheat parse_url by giving it a file url
        if (!$parsed = parse_url('file:'.$parts[1])) {
            throw new InvalidArgumentException("Unparseable url $url");
        }

        $parsed['scheme'] = $parts[0];

        return $this->addCredentials($parsed);
    }

    /**
     * Translate and decode username and password.
     *
     * @param array $parsed
     *
     * @return array
     */
    protected function addCredentials(array $parsed)
    {
        if (isset($parsed['pass'])) {
            $parsed['password'] = $parsed['pass'];
            unset($parsed['pass']);
        }
        if (isset($parsed['password'])) {
            $parsed['password'] = rawurldecode($parsed['password']);
        }
        if (isset($parsed['user'])) {
            $parsed['user'] = rawurldecode($parsed['user']);
        }
        return $parsed;
    }

    /**
     * @param string $scheme
     *
     * @return bool
     */
    protected static function isFlatScheme($scheme)
    {
        return isset(static::$flatSchemes[$scheme]) && static::$flatSchemes[$scheme];
    }
}
