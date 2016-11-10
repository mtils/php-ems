<?php


namespace Ems\Core\Exceptions;

use RuntimeException;
use Ems\Contracts\Core\Errors\NotFound;

/**
 * Throw a HandlerNotFoundException if a handler, an object to handle
 * something was not found. For example if you search a ZipFileReader
 * in an Repository of FileReaders.
 **/
class HandlerNotFoundException extends RuntimeException implements NotFound{}
