<?php
/**
 *  * Created by mtils on 24.10.20 at 08:58.
 **/

namespace Ems\View\Skeleton;


use Ems\Contracts\Core\Input;
use Ems\Core\Application;
use Ems\Core\Skeleton\Bootstrapper;
use Ems\Routing\InputHandler;
use Ems\View\InputRendererFactory;
use Ems\View\PhpRenderer;
use Ems\View\ViewFileFinder;

use const APP_ROOT;

class ViewBootstrapper extends Bootstrapper
{
    public function bind()
    {
        parent::bind();

        $this->app->bind(InputRendererFactory::class, function () {
            $factory = new InputRendererFactory();
            $this->addRenderers($factory);
            return $factory;
        }, true);

        $this->app->onAfter(InputHandler::class, function (InputHandler $handler) {
            $collection = $handler->middleware();
            $collection->add('view-renderer', InputRendererFactory::class);
        });

    }

    protected function addRenderers(InputRendererFactory $factory)
    {
        /** @var Application $app */
        $app = $this->app->get(Application::class);

        if (!$viewConfig = $app->config('view')) {
            return;
        }

        foreach ($viewConfig as $name=>$config) {
            $factory->extend($name, function (Input $input) use ($config) {
                if ($input->clientType() != $config['client-type']) {
                    return null;
                }
                if ($config['backend'] == 'php') {
                    return $this->createPhpRenderer($config);
                }
            });
        }
    }

    /**
     * @param array $config
     * @return PhpRenderer
     */
    protected function createPhpRenderer(array $config) : PhpRenderer
    {
        $paths = [];
        foreach ($config['paths'] as $path) {
            $paths[] = $path[0] == '/' ? $path : APP_ROOT . "/$path";
        }
        $finder = new ViewFileFinder();
        return new PhpRenderer($finder->setPaths($paths));
    }

}