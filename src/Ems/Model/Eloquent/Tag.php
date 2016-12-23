<?php

namespace Ems\Model\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Ems\Contracts\Model\Relation\Tag\TagWithGroups;
use Ems\Model\Relation\Tag\HoldsGroupsTrait;

class Tag extends EloquentModel implements TagWithGroups
{
    use IdentifiableByKeyTrait;
    use HoldsGroupsTrait;

    public static $nameKey = 'name';

    protected $guarded = ['id', 'group_id'];

    /**
     * {@inheritdoc}
     *
     * @return string
     *
     * @see \Ems\Contracts\Core\Named
     **/
    public function getName()
    {
        return $this->getAttribute(static::$nameKey);
    }

    /**
     * Return the name of the name column.
     *
     * @return string
     **/
    public function getNameKey()
    {
        return static::$nameKey;
    }
}
