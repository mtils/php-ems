<?php
/**
 *  * Created by mtils on 19.04.20 at 08:29.
 **/

namespace Models;

use Ems\Contracts\Core\DataObject;
use Ems\Core\Support\DataObjectTrait;
use Ems\Core\Support\ObjectReadAccess;
use Ems\Core\Support\ObjectWriteAccess;

/**
 * Class Contact
 *
 * @package Models
 *
 * @property int id
 * @property string first_name
 * @property string last_name
 * @property string company
 * @property string address
 * @property string city
 * @property string county
 * @property string postal
 * @property string phone1
 * @property string phone2
 * @property string created_at
 * @property string updated_at
 */
class Contact implements DataObject
{
    use DataObjectTrait;
    use ObjectReadAccess;
    use ObjectWriteAccess;
}