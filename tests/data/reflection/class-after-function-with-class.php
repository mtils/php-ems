<?php

/**
 *  * Created by mtils on 07.11.2021 at 06:34.
 **/

use Ems\Contracts\Core\Hookable;

$closure = function() {
    return new class() {};
};

class AfterFunctionWithClass {

    protected $test = '1';

    public function run(Hookable $hookable) : bool
    {
        return false;
    }
};