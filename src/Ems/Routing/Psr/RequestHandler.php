<?php
/**
 *  * Created by mtils on 10.07.2022 at 22:38.
 **/

namespace Ems\Routing\Psr;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function call_user_func;

class RequestHandler implements RequestHandlerInterface
{
    protected $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return call_user_func($this->callable, $request);
    }

}