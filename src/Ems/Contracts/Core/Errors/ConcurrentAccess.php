<?php

namespace Ems\Contracts\Core\Errors;

/**
 * This is an empty interface for exceptions.
 * The exception will mark that a resource, variable or file
 * was accessed while another process accessing it
 * (like a failed flock()).
 **/
interface ConcurrentAccess
{
}
