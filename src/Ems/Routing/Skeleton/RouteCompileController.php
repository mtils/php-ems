<?php
/**
 *  * Created by mtils on 05.06.2022 at 08:48.
 **/

namespace Ems\Routing\Skeleton;

use Ems\Contracts\Core\Storage;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Routing\Router;
use Ems\Contracts\Routing\RouteRegistry;
use Ems\Routing\CompilableRouter;
use Ems\Skeleton\Connection\ConsoleOutputConnection;

use function method_exists;

class RouteCompileController
{
    /**
     * @var Storage
     */
    private $storage;

    public function compile(RouteRegistry $registry, Router $router, ConsoleOutputConnection $out) : int
    {
        $storageClass = Type::short($this->storage);
        $message = "Compiling routes into cache stored by <comment>$storageClass</comment>";
        if ($target = $this->getTarget()) {
            $message .= " at <comment>$target</comment>...";
        }
        $out->line($message);

        $compiledData = $registry->compile($router);
        $this->storage->clear();

        foreach ($compiledData as $key=>$value) {
            $this->storage->offsetSet($key, $value);
        }

        if ($this->storage->isBuffered()) {
            $this->storage->persist();
            $out->line('<mute>Storage is buffered.</mute> <info>Manually persisted storage. Successfully finished.</info>');
            return 0;
        }

        $out->line('<mute>Storage is unbuffered.</mute> <info>Trusting automatic save mechanism. Successfully finished.</info>');
        return 0;
    }

    /**
     * Show some status information about the cache.
     *
     * @param ConsoleOutputConnection $out
     * @return int
     */
    public function status(ConsoleOutputConnection $out) : int
    {
        $storageClass = Type::short($this->storage);
        $message = "Checking route cache stored by <comment>$storageClass</comment>";
        if ($target = $this->getTarget()) {
            $message .= " at <comment>$target</comment>...";
        }

        $out->line($message);

        $compiledData = $this->storage->toArray();
        if ($this->hasRoutingData($compiledData)) {
            $out->line('<info>Routes are cached</info>');
            return 0;
        }
        $out->line('<comment>Routes are not cached</comment>');

        return 0;
    }

    public function clear(ConsoleOutputConnection $out) : int
    {
        $storageClass = Type::short($this->storage);
        $message = "Delete route cache stored by $storageClass";
        if ($target = $this->getTarget()) {
            $message .= " at $target...";
        }
        $out->line($message);
        $data = $this->storage->toArray();
        if (!$this->hasRoutingData($data)) {
            $out->line('<comment>Routes cache was empty or did not exist. No need to clear it. Aborted</comment>');
            return 0;
        }

        $this->storage->clear();
        if ($this->storage->isBuffered()) {
            $this->storage->persist();
        }
        $out->line('<info>Routes cache was cleared.</info>');
        return 0;
    }

    /**
     * @return Storage
     */
    public function getStorage(): Storage
    {
        return $this->storage;
    }

    /**
     * @param Storage $storage
     */
    public function setStorage(Storage $storage): void
    {
        $this->storage = $storage;
    }

    protected function hasRoutingData(array $compiledData) : bool
    {
        return isset($compiledData[CompilableRouter::KEY_VALID]) && $compiledData[CompilableRouter::KEY_VALID];
    }

    protected function getTarget() : string
    {
        if (method_exists($this->storage, 'getUrl')) {
            return $this->storage->getUrl();
        }
        return '';
    }
}