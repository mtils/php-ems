<?php
/**
 *  * Created by mtils on 19.04.20 at 07:00.
 **/

namespace Models\Ems;


use Ems\Model\StaticClassMap;
use Models\ProjectType;

class ProjectTypeMap extends StaticClassMap
{
    const ID            = 'id';
    const NAME          = 'name';
    const CREATED_AT    = 'created_at';
    const UPDATED_AT    = 'updated_at';

    const ORM_CLASS = ProjectType::class;

    const STORAGE_NAME = 'project_types';

    const STORAGE_URL = 'database://default';

}