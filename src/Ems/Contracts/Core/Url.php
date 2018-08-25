<?php

namespace Ems\Contracts\Core;

use ArrayAccess;
use Ems\Core\Collections\StringList;
use IteratorAggregate;
use Ems\Contracts\Core\Arrayable;

/**
 * This is a helper object to easily work with urls
 * Use ArrayAccess to access the query values (?foo=bar)
 * IteratorAggregate to walk over the query.
 *
 * Every method returns a new Url instance for chaining
 * The magic get method allows to get the values from the url
 * So basically you write to a Url by methods and read from it by property
 * access.
 * An url has to be immutable
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
interface Url extends Stringable, ArrayAccess, IteratorAggregate, Copyable, Arrayable
{
    /**
     * Set the scheme.
     *
     * @param string $scheme (optional)
     *
     * @return self
     **/
    public function scheme($scheme);

    /**
     * Set the user.
     *
     * @param string $user
     *
     * @return self
     **/
    public function user($user);

    /**
     * Set the password.
     *
     * @param string $password
     *
     * @return self
     **/
    public function password($password);

    /**
     * Set the host.
     *
     * @param string $host
     * @retrurn self
     **/
    public function host($host);

    /**
     * Set the port.
     *
     * @param int $port
     *
     * @return self
     **/
    public function port($port);

    /**
     * Set the path of the query.
     *
     * @param string|array $path
     *
     * @return self
     **/
    public function path($path);

    /**
     * Append a segment to the path.
     *
     * @param string|array $segment
     *
     * @return self
     **/
    public function append($segment);

    /**
     * Remove the last $count segments from the path.
     *
     * @param int $count
     *
     * @return self
     **/
    public function pop($count = 1);

    /**
     * Prepend a segment to the path.
     *
     * @param string|array $segment
     *
     * @return self
     **/
    public function prepend($segment);

    /**
     * Set a query value (same as offsetSet but returns itself)
     * Set a key and value or many keys by passing an array
     * pass a leading ? to replace the whole query in the copy
     * Passing an empty string clears the query.
     *
     * @param string|array $key
     * @param mixed        $value (optional)
     *
     * @return self
     **/
    public function query($key, $value = null);

    /**
     * Remove a query value. Pass on key, an array of keys or multiple
     * arguments to remove the keys from the query.
     *
     * @param string|array $key
     *
     * @return self
     */
    public function without($key);

    /**
     * Set the fragmenbt part (#top).
     *
     * @param string $fragment
     *
     * @return self
     **/
    public function fragment($fragment);

    /**
     * Return if this url is relative.
     *
     * @return bool
     **/
    public function isRelative();

    /**
     * Return if the whole url is empty.
     *
     * @return bool
     **/
    public function isEmpty();

    /**
     * Return the $part of this query
     * Returns strings except on:
     * host: returns an int
     * path: returns an array
     * query: returns an array.
     *
     * @param $part
     *
     * @return mixed
     **/
    public function __get($part);

    /**
     * Compare two urls. For absolute equality use the normal == operator
     * with stringify. ("$url1" == "$url2").
     * Single parts can be checked with $url->path->equals($url-2->path)
     * or just $url->user == $url2->user.
     *
     * This method here checks every passed parts. If all match it returns true.
     *
     * @param string|Url   $other
     * @param string|array $parts
     *
     * @return bool
     */
    public function equals($other, $parts=['scheme', 'user', 'password', 'host', 'path']);
}
