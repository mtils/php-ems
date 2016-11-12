<?php

namespace Ems\Model\Relation\Tag;

use OutOfBoundsException;
use Ems\Contracts\Model\Relation\Tag\Tag;

/**
 * @see \Ems\Contracts\Model\Relation\Tag\HoldsTags
 **/
trait HoldsTagsTrait
{
    /**
     * @var array
     **/
    protected $_tags = [];

    /**
     * {@inheritdoc}
     *
     * @return \Traversable
     **/
    public function getTags()
    {
        return $this->_tags;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Traversable<Ems\Contracts\Model\Relation\Tag> $tags
     *
     * @return self
     **/
    public function setTags($tags)
    {
        $this->_tags = (array) $tags;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Model\Relation\Tag $tag
     *
     * @return self
     */
    public function attachTag(Tag $tag)
    {
        if (!$this->hasTag($tag)) {
            $this->_tags[] = $tag;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Model\Relation\Tag $tag
     *
     * @return self
     */
    public function detachTag(Tag $tag)
    {
        if (!$this->hasTag($tag)) {
            throw new OutOfBoundsException('Tag '.$tag->getName().' is not attached');
        }

        $this->_tags = array_filter($this->_tags, function ($knownTag) use ($tag) {
            return $tag->getId() != $knownTag->getId();
        });

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Model\Relation\Tag $tag
     *
     * @return bool
     **/
    public function hasTag(Tag $tag)
    {
        foreach ($this->_tags as $knownTag) {
            if ($knownTag->getId() == $tag->getId()) {
                return true;
            }
        }

        return false;
    }
}
