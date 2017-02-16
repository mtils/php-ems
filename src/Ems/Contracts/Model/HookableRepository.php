<?php


namespace Ems\Contracts\Model;


use Ems\Contracts\Core\HasMethodHooks;

/**
 * An hookable Repository allows hooks in every method. This
 * is the preferred way to build highly customizable applications.
 * In cmsable modules for example it allows to change the behaviour
 * of every repository.
 **/
interface HookableRepository extends Repository, HasMethodHooks
{
}
