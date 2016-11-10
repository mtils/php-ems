<?php

namespace Ems\Core\Exceptions;

use Ems\Contracts\Core\Errors\UnSupported;
use OutOfBoundsException;

class UnsupportedParameterException extends OutOfBoundsException implements UnSupported
{}
