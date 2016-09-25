<?php


namespace Ems\Core\Collections;

use Ems\Contracts\Core\NotFound;
use OutOfBoundsException;

class ItemNotFoundException extends OutOfBoundsException implements NotFound
{}
