<?php
/**
 *  * Created by mtils on 24.10.20 at 09:46.
 **/

namespace Ems\View;


use Ems\Contracts\Core\Extendable;
use Ems\Contracts\Core\Renderable;
use Ems\Contracts\Core\Renderer;
use Ems\Contracts\Routing\Input;
use Ems\Core\Patterns\ExtendableTrait;
use Ems\Core\Response;

class InputRendererFactory implements Extendable
{
    use ExtendableTrait;

    /**
     * Create a renderer for $input and $renderable.
     *
     * @param Input $input
     * @param Renderable|null $renderable
     *
     * @return Renderer|null
     */
    public function renderer(Input $input, Renderable $renderable = null): ?Renderer
    {
        return $this->callUntilNotNull([$input, $renderable]);
    }

    /**
     * Use the factory as middleware.
     *
     * @param Input $input
     * @param callable $next
     *
     * @return Response
     */
    public function __invoke(Input $input, callable $next)
    {
        /** @var Response $response */
        $response = $next($input);
        $payload = $response->payload;

        if (!$payload instanceof Renderable) {
            return $response;
        }

        if ($payload->getRenderer()) {
            return $response;
        }

        $this->assignRenderer($input, $payload);

        return $response;
    }

    /**
     * Create a renderer and assign it to the renderable.
     *
     * @param Input $input
     * @param Renderable $item
     */
    protected function assignRenderer(Input $input, Renderable $item)
    {
        if ($renderer = $this->renderer($input, $item)) {
            $item->setRenderer($renderer);
        }
    }

}