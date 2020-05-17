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
use Models\Contact;

/**
 * Class User
 *
 * @package Models
 *
 * @property int      id
 * @property string   email
 * @property string   password
 * @property string   web
 * @property int      contact_id
 * @property DateTime created_at
 * @property DateTime updated_at
 * @property Contact  contact
 * @property Token[]  tokens
 */
class User implements DataObject
{
    use DataObjectTrait;
    use ObjectReadAccess;
    use ObjectWriteAccess;
}