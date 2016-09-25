<?php


namespace Ems\Core;

use OutOfBoundsException;
use Ems\Contracts\Core\NotFound;

/**
 * Throw a DetectionFailedException if you analyse or detect something
 * and that fails. For example if you try to guess the mimetype of a file
 * or you need to extract an email adress out of a string
 **/
class DetectionFailedException extends OutOfBoundsException implements NotFound{}