<?php 

namespace Ems\Model\Eloquent;

use Ems\Contracts\Core\Errors\NotFound;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NotFoundException extends ModelNotFoundException implements NotFound {}
