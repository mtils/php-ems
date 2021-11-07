<?php
/**
 *  * Created by mtils on 07.11.2021 at 12:40.
 **/

namespace Ems\Contracts\Model\Schema;

interface MigrationRunner
{
    public function upgrade(string $file,   bool $simulate=false);
    public function downgrade(string $file, bool $simulate=false);
}