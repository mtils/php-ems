<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 11.11.17
 * Time: 07:31
 */

namespace Ems\Contracts\Core;

/**
 * Interface ConnectionPool
 *
 * The ConnectionPool is the central place to create or return connections.
 * Give connections a name or create connections by an url.
 *
 * @package Ems\Contracts\Core
 */
interface ConnectionPool extends Extendable
{
    /**
     * Return a connection or create a new one. No $nameOrUrl means the "default"
     * connection, which is mostly the database connection.
     *
     * @param string|Url $nameOrUrl (optional)
     *
     * @return Connection
     **/
    public function connection($nameOrUrl=null);

    /**
     * Return the default connection name. This is used for connection()
     * without a parameter.
     * The default connection will mostly be the database connection.
     *
     * @return string
     **/
    public function defaultConnectionName();

    /**
     * Set the default connection name. This is used for connection()
     * without a parameter.
     * The default connection will mostly be the database connection.
     *
     * @param string $name
     *
     * @return self
     **/
    public function setDefaultConnectionName($name);
}