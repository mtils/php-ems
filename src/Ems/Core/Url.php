<?php

namespace Ems\Core;

use Ems\Contracts\Core\Url as UrlContract;
use InvalidArgumentException;
use Ems\Core\Support\StringableTrait;
use Ems\Core\Collections\StringList;
use ArrayIterator;
use RuntimeException;

class Url implements UrlContract
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
     * @var \Ems\Core\Collections\StringList
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
    public function renderString()
    {
        $string = '';

        if ($this->scheme) {
            $slashes = $this->isFlat() ? '' : '//';

            $string .= "{$this->scheme}:$slashes";
        }

        if ($authority = $this->getAuthority()) {
            $string .= $authority;
        }

        if ($this->path && !$this->isFlat()) {
            $prefix = $authority || $this->path->getPrefix() ? '/' : '';
            $string .= $prefix.ltrim((string) $this->path, '/');
        }

        if ($this->query) {
            $string .= '?'.http_build_query($this->query);
        }

        if ($this->fragment) {
            $string .= "#{$this->fragment}";
        }

        return $string;
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
     * @return int
     **/
    public function getIterator()
    {
        return new ArrayIterator($this->query);
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
            return new static(array_merge($this->selfToArray(), $attributes));
        }

        $queryAttributes = $attributes['query'];
        unset($attributes['query']);

        $newAttributes = array_merge($this->selfToArray(), $attributes);

        $newAttributes['query'] = $this->mergeOrReplaceQuery($queryAttributes);

        return new static($newAttributes);
    }

    /**
     * Returns if this is a flat url.
     * mailto: and news: are flat f.e.
     *
     * @see self::$flatSchemes
     *
     * @param string $scheme
     *
     * @return bool
     */
    protected function isFlat()
    {
        if (!$this->scheme) {
            return false;
        }

        return isset(static::$flatSchemes[$this->scheme]) && static::$flatSchemes[$this->scheme];
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
    protected function getAuthority()
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
    protected function getUserInfo()
    {
        $userinfo = $this->user;

        if ($this->password) {
            $userinfo .= ":{$this->password}";
        }

        return $userinfo;
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

    protected function selfToArray()
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

        $path = is_array($path) ? $path : explode('/', trim($path, '/ '));

        $this->path->setSource($path);

        if ($hasLeadingSlash || $this->scheme) {
            $this->path->setPrefix('/');

            return $this;
        }

        $this->path->setPrefix('');

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
        if (!$parsed = parse_url($url)) {
            throw new InvalidArgumentException("Unparseable url $url");
        }
        if (isset($parsed['pass'])) {
            $parsed['password'] = $parsed['pass'];
        }

        return $parsed;
    }
}
