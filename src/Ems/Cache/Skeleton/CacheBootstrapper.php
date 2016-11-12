<?php

namespace Ems\Cache\Skeleton;

use Ems\Core\Skeleton\Bootstrapper;
use Ems\Contracts\Cache\Cacheable;
use Ems\Contracts\Cache\Cache as CacheContract;
use Ems\Contracts\Cache\Categorizer;
use Ems\Cache\CategorizerChain;
use Ems\Cache\Cache;
use Ems\Cache\Categorizer\DefaultCategorizer;

class CacheBootstrapper extends Bootstrapper
{
    protected $categorizersAdded = false;

    protected $singletons = [
        Cache::class => [CacheContract::class, 'ems::cache'],
        CategorizerChain::class => [Categorizer::class, 'ems::cache.categorizer'],
    ];

    protected $bindings = [];

    public function bind()
    {
        parent::bind();

        // Binding the cache also to its own class name (without recursion)
        $this->app->resolving(Cache::class, function (Cache $cache, $app) {
            if (!$app->bound(Cache::class)) {
                $app->instance(Cache::class, $cache);
            }
        });

        $this->app->resolving(CategorizerChain::class, function (CategorizerChain $chain, $app) {
            $this->addCategorizers($chain, $app);
        });

        $this->app->resolving(Cacheable::class, function (Cacheable $cacheable, $app) {
            $cacheable->setCache($app(CacheContract::class));
        });
    }

    protected function addCategorizers(CategorizerChain $chain, $app)
    {

        // Double triggers of resolving :-(
        if ($this->categorizersAdded) {
            return;
        }

        $chain->add($app(DefaultCategorizer::class, [$chain]));

        $this->categorizersAdded = true;
    }
}
