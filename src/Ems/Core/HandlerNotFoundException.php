<?php


namespace Ems\Core;

use RuntimeException;
use Ems\Contracts\Core\NotFound;

/**
 * Throw a HandlerNotFoundException if a handler, an object to handle
 * something was not found. For example if you search a ZipFileReader
 * in an Repository of FileReaders.
 **/
class HandlerNotFoundException extends RuntimeException implements NotFound{}
