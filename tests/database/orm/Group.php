<?php
/**
 *  * Created by mtils on 19.04.20 at 08:29.
 **/

namespace Models;

use DateTime;
use Ems\Contracts\Core\DataObject;
use Ems\Core\Support\DataObjectTrait;
use Ems\Core\Support\ObjectReadAccess;
use Ems\Core\Support\ObjectWriteAccess;

/**
 * Class Group
 *
 * @package Models
 *
 * @property int    id
 * @property string name
 * @property DateTime created_at
 * @property DateTime updated_at
 */
class Group implements DataObject
{
    use DataObjectTrait;
    use ObjectReadAccess;
    use ObjectWriteAccess;
}