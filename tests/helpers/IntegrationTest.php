<?php

namespace Ems;


use Ems\Core\LocalFilesystem;
use Ems\Core\Application;

class IntegrationTest extends TestCase
{
    protected $_app;

    public function app($binding=null, array $parameters=[])
    {
        if (!$this->_app) {
            $this->_app = new Application(realpath(__DIR__.'/../../'));
        }

        return ($binding ? $this->_app->__invoke($binding, $parameters) : $this->_app);
    }
}
