<?php
/**
 *  * Created by mtils on 09.11.2021 at 23:17.
 **/

namespace Ems\Contracts\Model\Exceptions;

class MigratorInstallationException extends MigratorException
{
    const NOT_INSTALLED   = 4040;
    const NOT_INSTALLABLE = 5000;
}