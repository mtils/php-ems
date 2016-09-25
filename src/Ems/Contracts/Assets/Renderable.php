<?php


namespace Ems\Contracts\Assets;

use Ems\Contracts\Core\Renderable as BaseRenderable;


interface Renderable extends BaseRenderable
{
    /**
     * Returns the group name
     *
     * @return string
     **/
    public function group();

}
