<?php


namespace Ems\Model\Eloquent;

/**
 * This trait helps to build a front cover object out of one ore more covers.
 **/
trait FrontCoverByAttribute
{
    /**
     * Return an image representing this object
     * Optionally pass a size int for the right icon
     * size. Return an uri which points to the icon file
     * or name.
     * Uris could be:
     * http://example.org/avatar.png
     * icon://search
     * fontawesome://fa-trash.
     *
     * @param int $size (optional)
     *
     * @return string
     **/
    public function getFrontCover($size = 0)
    {
        return $this->buildUri(
            $this->getAttribute(
                $this->getFrontCoverAttributeName($size)
            )
        );
    }

    /**
     * Return an attribute name for a front cover with size $size.
     * The base implementation returns always the same name.
     *
     * @param int $size
     *
     * @return string
     **/
    public function getFrontCoverAttributeName($size)
    {
        return 'preview_image';
    }

    /**
     * Build an uri out of the database content.
     *
     * @param string $plainFrontCover
     *
     * @return string
     **/
    public function buildUri($plainFrontCover)
    {
        return $plainFrontCover;
    }
}
