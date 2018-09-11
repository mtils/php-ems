<?php
/**
 *  * Created by mtils on 09.09.18 at 08:34.
 **/

namespace Ems\Contracts\Core;

/**
 * Interface IdGenerator
 *
 * Generate a (unique) id. Use this to create random bytes for tokens or just
 * the next number in your database table or an uuid ir whatever.
 *
 * This is not a hash object. So you can not just implement an MD5IdGenerator
 * by passing some $salt and return the md5. This would return the same id
 * ever and ever again.
 *
 * Passing some $salt should lead to different Ids on every call.
 *
 * @package Ems\Contracts\Core
 */
interface IdGenerator
{

    /**
     * Generate an id or random sequence. Do not use this method for hashing.
     * Try to generate fresh sequences or numbers on every call.
     *
     * Passing $salt could be the last known id so perhaps its easier to
     * increment. Or a base value that will affect or better the entropy.
     *
     * If your generator is restricted to a distinct format like Microsoft GUID
     * you must throw an Unsupported Exception if someones passing a desired
     * length that is not supported by the format..
     *
     * @param string|int $salt (optional) Optionally pass something that will affect the result
     * @param int        $length (optional) Optionally pass a desired length.
     * @param bool       $asciiOnly (default: true) Don't create binary blobs
     *
     * @return string|int
     */
    public function generate($salt=null, $length=0, $asciiOnly=true);

    /**
     * Let the generator generate Ids until the callable returns true.
     * Don't run into the boolean trap. True means "this works". So for a check
     * in an array it would be return !in_array($known).
     *
     * @example $idGenerator->until(function ($id) {}
     *              return $id >= max($knownIds);
     *          )->generate();
     *
     * @param callable $isUniqueChecker
     *
     * @return $this
     */
    public function until(callable $isUniqueChecker);

    /**
     * Return 'int' or 'string'
     *
     * @return string
     */
    public function idType();

    /**
     * Return the minimum length or int
     *
     * @return int
     */
    public function min();

    /**
     * Return the maximum length or int
     *
     * @return int
     */
    public function max();

    /**
     * Return the cryptographically strength of this generator.
     * 0 means no strength at all (in case of just incrementing numbers for
     * example) and 99 would be a yet unknown successor of blowfish or its
     * pendants.
     *
     * @return int
     */
    public function strength();

    /**
     * Return true if this generator is supported.
     *
     * @return bool
     */
    public function isSupported();
}