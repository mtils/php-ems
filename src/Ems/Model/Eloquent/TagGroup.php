<?php

namespace Ems\Model\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Ems\Contracts\Model\Relation\Tag\TagGroup as TagGroupContract;

class TagGroup extends EloquentModel implements TagGroupContract
{
    use IdentifiableByKeyTrait;

    public static $nameKey = 'name';

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
}
