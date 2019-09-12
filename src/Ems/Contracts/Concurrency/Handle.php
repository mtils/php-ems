<?php
/**
 *  * Created by mtils on 07.09.19 at 07:13.
 **/

namespace Ems\Contracts\Concurrency;


use DateTimeInterface;
use Ems\Core\Support\ObjectReadAccess;

/**
 * Class Handle
 *
 * This is an immutable object that will return as the handle for your lock,
 * mutex or semaphore.
 *
 * @package Ems\Contracts\Concurrency
 *
 * @property-read DateTimeInterface validUntil
 * @property-read string uri
 * @property-read string token
 */
class Handle
{
    use ObjectReadAccess;

    protected $_properties = [];

    /**
     * Lock constructor.
     *
     * @param $uri
     * @param string $token
     * @param DateTimeInterface $validUntil
     */
    public function __construct($uri, $token = '', DateTimeInterface $validUntil = null
    )
    {
        $this->_properties = [
            'validUntil' => $validUntil,
            'uri'        => $uri,
            'token'      => $token
        ];
    }

}