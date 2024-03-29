<?php
/**
 *  * Created by mtils on 24.10.20 at 08:58.
 **/

namespace Ems\View\Skeleton;


use Ems\Console\AnsiRenderer;
use Ems\Contracts\Core\Renderable;
use Ems\Contracts\Routing\Input;
use Ems\Routing\ArgvInput;
use Ems\Routing\InputHandler;
use Ems\Skeleton\Bootstrapper;
use Ems\View\InputRendererFactory;
use Ems\View\PhpRenderer;
use Ems\View\ViewFileFinder;

use function ltrim;

use const APP_ROOT;

class ViewBootstrapper extends Bootstrapper
{
    public function bind()
    {
        parent::bind();

        $this->container->bind(InputRendererFactory::class, function () {
            $factory = new InputRendererFactory();
            $this->addRenderers($factory);
            return $factory;
        }, true);

        $this->container->onAfter(InputHandler::class, function (InputHandler $handler) {
            $collection = $handler->middleware();
            $collection->add('view-renderer', InputRendererFactory::class);
        });

    }

    protected function addRenderers(InputRendererFactory $factory)
    {

        $factory->extend('console-view-renderer', function (Input $input, Renderable $renderable=null) {
            return $this->createConsoleRenderer($input, $renderable);
        });

        if (!$viewConfig = $this->app->config('view')) {
            return;
        }

        foreach ($viewConfig as $name=>$config) {
            $factory->extend($name, function (Input $input) use ($config) {
                if ($input->getClientType() != $config['client-type']) {
                    return null;
                }
                if ($config['backend'] == 'php') {
                    return $this->createPhpRenderer($config)->share('input', $input);
                }
                return null;
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
        $finder = (new ViewFileFinder())->setPaths($paths);
        if (isset($config['extension']) && $config['extension']) {
            $finder->setExtension('.'.ltrim($config['extension'], '.'));
        }
        /** @var PhpRenderer $renderer */
        $renderer = $this->container->create(PhpRenderer::class, [$finder]);
        $renderer->setContainer($this->container);
        return $renderer;
    }

    protected function createConsoleRenderer(Input $input, Renderable $renderable=null)
    {
        if (!$input instanceof ArgvInput || !$renderable) {
            return null;
        }
        /** @var AnsiRenderer $renderer */
        $renderer = $this->container->create(AnsiRenderer::class);
        return $renderer->canRender($renderable) ? $renderer : null;
    }
}