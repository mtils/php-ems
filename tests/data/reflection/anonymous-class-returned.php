<?php

/**
 *  * Created by mtils on 07.11.2021 at 06:34.
 **/

use Ems\Contracts\Core\Hookable;

return new class() {

    protected $test = '1';

    public function run(Hookable $hookable) : bool
    {
        return false;
    }
};