<?php


namespace Ems\Cache\Exception;


use Ems\Contracts\Core\NotFound;
use OutOfBoundsException;

class CacheMissException extends OutOfBoundsException implements NotFound
{
}
