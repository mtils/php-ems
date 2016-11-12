<?php

namespace Ems\Skeleton;

use Ems\Core\Application as BaseApplication;
use Ems\Contracts\Core\IOCContainer;

class Application extends BaseApplication
{
    /**
     * @var \Ems\Skeleton\BootManager
     **/
    protected $bootManager;

    public function __construct($path, IOCContainer $container = null)
    {
        parent::__construct($path, $container);
    }

    public function bootManager()
    {
        if (!$this->bootManager) {
            $this->bootManager = $this->container->__invoke(BootManager::class);
            $this->bootManager->setApplication($this);
        }

        return $this->bootManager;
    }
}
