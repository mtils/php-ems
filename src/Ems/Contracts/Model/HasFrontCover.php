<?php

namespace Ems\Contracts\Model;

use Ems\Contracts\Core\Identifiable;

interface HasFrontCover extends Identifiable
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
    public function getFrontCover($size = 0);
}
