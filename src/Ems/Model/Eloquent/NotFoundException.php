<?php 

namespace Ems\Model\Eloquent;

use Ems\Contracts\Core\NotFound;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NotFoundException extends ModelNotFoundException implements NotFound {}