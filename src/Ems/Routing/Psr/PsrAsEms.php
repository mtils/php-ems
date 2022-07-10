<?php
/**
 *  * Created by mtils on 10.07.2022 at 22:30.
 **/

namespace Ems\Routing\Psr;

use Ems\Contracts\Routing\Input;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class PsrAsEms
{
    /**
     * @var MiddlewareInterface|null
     */
    protected $psrMiddleware;

    public function __invoke(Input $input, callable $next)
    {
        if (!$input instanceof ServerRequestInterface || !$this->psrMiddleware) {
            return $next($input);
        }
        return $this->psrMiddleware->process($input, new RequestHandler($next));
    }

    public function getPsrMiddleware() : ?MiddlewareInterface
    {
        return $this->psrMiddleware;
    }
}