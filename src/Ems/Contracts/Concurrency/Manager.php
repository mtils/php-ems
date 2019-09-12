<?php
/**
 *  * Created by mtils on 07.09.19 at 07:04.
 **/

namespace Ems\Contracts\Concurrency;

/**
 * Interface Manager
 *
 * The Concurrency Manager is the central management of your application locks.
 * In PHP this mostly means to prevent multiple started php scripts / processes
 * should not collide because we dont have one running application but multiple
 * scripts (performing requests, cron jobs, queue jobs).
 * On one machine (like local development) you usually need file locking. In an
 * cloud system (ecs cluster) you need distributed network locks like redis or
 * database locks.
 *
 * @package Ems\Contracts\Concurrency
 */
interface Manager
{
    /**
     * Lock the uri for $ttlMilliseconds. Uri is just some resource name or
     * something you named. A process name, command, action...
     * Return null if the resource is already locked.
     *
     * If $ttlMilliseconds === null use the default. Put it to 0 to set it to
     * infinite.
     *
     * Giving the lock an expiry (ttl) results in:
     *
     * Depending on the backend the lock will just disappear and outside
     * of the script that created the (now expired) lock there is no chance to
     * catch that state
     *
     * 2. Unlock should therefore check if it was expired and record that error
     * because other processes then potentially did collide with the too long
     * running process.
     *
     * @param string $uri
     * @param int    $ttlMilliseconds (optional)
     *
     * @return Handle|null
     */
    public function lock($uri, $ttlMilliseconds=null);

    /**
     * Release the handle you got from self::lock()
     *
     * @param Handle $handle
     *
     * @return void
     */
    public function release(Handle $handle);

    /**
     * Perform the next lock by retrying it $times with a pause of $delay
     * milliseconds between the tries.
     *
     * Default is one try. One try means false if lock did not success.
     * Endless waiting like LOCK_EX without LOCK_NB is not supported.
     *
     * Because we assume that we have basically one "configuration" per uri
     * things are excluding each other.
     * So in one place of your application you decide to have 1 try for $uri.
     * There will be no other place were you decide to have a different number
     * of tries for that same uri.
     *
     * @example $manager->retry(5)->lock()
     *
     * @param int $times
     * @param int $delay
     *
     * @return self
     */
    public function retry($times=3, $delay=200);

    /**
     * Run the callable locked. Return its return value.
     *
     * @param callable $run
     * @param int $ttlMilliseconds (default:0)
     *
     * @return mixed
     */
    public function run(callable $run, $ttlMilliseconds=null);

    /**
     * Do a double check lock operation.
     *
     * @see https://en.wikipedia.org/wiki/Double-checked_locking
     *
     * @example $manager->when(f())->run(f2())
     *
     * @param callable $run
     *
     * @return self
     */
    public function when(callable $run);

}