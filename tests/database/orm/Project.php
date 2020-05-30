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
 * Class Project
 *
 * @package Models
 *
 * @property int            id
 * @property string         name
 * @property int            type_id
 * @property int            owner_id
 * @property DateTime       created_at
 * @property DateTime       updated_at
 * @property ProjectType    type
 * @property File[]         files
 * @property User           owner
 */
class Project implements DataObject
{
    use DataObjectTrait;
    use ObjectReadAccess;
    use ObjectWriteAccess;
}