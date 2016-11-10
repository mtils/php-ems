<?php


namespace Ems\Core\Collections;

use Ems\Contracts\Core\Errors\NotFound;
use OutOfBoundsException;

class ItemNotFoundException extends OutOfBoundsException implements NotFound
{}
