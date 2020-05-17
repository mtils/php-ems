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
use Models\User;

/**
 * Class Token
 *
 * @package Models
 *
 * @property int    id
 * @property int      user_id
 * @property int      token_type
 * @property string   token
 * @property DateTime expires_at
 * @property DateTime created_at
 * @property DateTime updated_at
 * @property User     user
 */
class Token implements DataObject
{
    use DataObjectTrait;
    use ObjectReadAccess;
    use ObjectWriteAccess;
}