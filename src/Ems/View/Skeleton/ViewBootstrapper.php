<?php
/**
 *  * Created by mtils on 24.10.20 at 08:58.
 **/

namespace Ems\View\Skeleton;


use Ems\Core\Skeleton\Bootstrapper;
use Ems\Routing\InputHandler;
use Ems\View\InputRendererFactory;

class ViewBootstrapper extends Bootstrapper
{
    public function bind()
    {
        parent::bind();

        $this->app->bind(InputRendererFactory::class, function () {
            return new InputRendererFactory();
        }, true);

        $this->app->onAfter(InputHandler::class, function (InputHandler $handler) {
            $collection = $handler->middleware();
            $collection->add('view-renderer', InputRendererFactory::class);
        });

    }

}