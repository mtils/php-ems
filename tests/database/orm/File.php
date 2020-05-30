<?php
/**
 *  * Created by mtils on 30.05.20 at 06:40.
 **/

namespace Models;

use DateTime;
use Ems\Contracts\Core\DataObject;
use Ems\Core\Support\DataObjectTrait;
use Ems\Core\Support\ObjectReadAccess;
use Ems\Core\Support\ObjectWriteAccess;

/**
 * Class File
 *
 * @package Models
 *
 * @property int        id
 * @property string     name
 * @property DateTime   created_at
 * @property DateTime   updated_at
 */
class File implements DataObject
{
    use DataObjectTrait;
    use ObjectReadAccess;
    use ObjectWriteAccess;
}