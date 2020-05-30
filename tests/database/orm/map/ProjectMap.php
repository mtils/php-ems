<?php
/**
 *  * Created by mtils on 19.04.20 at 07:00.
 **/

namespace Models\Ems;


use Ems\Contracts\Model\Relationship;
use Ems\Model\StaticClassMap;
use Models\File;
use Models\Project;
use Models\ProjectType;
use Models\User;

class ProjectMap extends StaticClassMap
{
    const ID            = 'id';
    const NAME          = 'name';
    const TYPE_ID       = 'type_id';
    const OWNER_ID      = 'owner_id';
    const CREATED_AT    = 'created_at';
    const UPDATED_AT    = 'updated_at';

    const ORM_CLASS = Project::class;

    const STORAGE_NAME = 'projects';

    const STORAGE_URL = 'database://default';

    public static function owner() : Relationship
    {
        return static::relateTo(User::class, UserMap::ID, self::OWNER_ID)
            ->makeRequired(true);
    }

    public static function type() : Relationship
    {
        return static::relateTo(ProjectType::class, ProjectTypeMap::ID, self::TYPE_ID)
            ->makeRequired(true);
    }

    public static function files() : Relationship
    {
        return static::relateTo(File::class, FileMap::ID, self::ID)
            ->hasMany(true)
            ->junction('project_file', 'project_id', 'file_id');
    }
}