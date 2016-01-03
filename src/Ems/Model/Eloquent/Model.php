<?php 

namespace Ems\Model\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Ems\Contracts\Core\Identifiable;

class Model extends EloquentModel implements Identifiable
{
    use IdentifiableByKeyTrait;
}