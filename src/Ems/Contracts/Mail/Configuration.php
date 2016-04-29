<?php 

namespace Ems\Contracts\Mail;

use Ems\Contracts\Core\Named;

interface Configuration extends Named
{

    public function group();

    public function recipientList();

    public function template();

}