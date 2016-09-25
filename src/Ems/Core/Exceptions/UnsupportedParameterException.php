<?php

namespace Ems\Core\Exceptions;

use Ems\Contracts\Core\Unsupported;
use OutOfBoundsException;

class UnsupportedParameterException extends OutOfBoundsException implements Unsupported
{}
