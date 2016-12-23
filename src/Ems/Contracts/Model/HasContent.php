<?php

namespace Ems\Contracts\Model;

use Ems\Contracts\Core\Identifiable;

/**
 * A object which has content has on main text, html or binary content.
 **/
interface HasContent extends Identifiable
{
    /**
     * Return the content of this object.
     *
     * @return string
     **/
    public function getContent();

    /**
     * Return the mimetype of its content.
     *
     * @return string
     **/
    public function getContentMimeType();
}
