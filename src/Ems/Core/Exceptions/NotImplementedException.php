<?php

namespace Ems\Core\Exceptions;

use Ems\Contracts\Core\UnSupported;
use OutOfBoundsException;

class NotImplementedException extends OutOfBoundsException implements UnSupported
{}
