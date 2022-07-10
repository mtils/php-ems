<?php
/**
 *  * Created by mtils on 10.07.2022 at 21:56.
 **/

namespace Ems\Routing\Psr;

use Ems\Contracts\Routing\InputHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use TypeError;

use function call_user_func;
use function get_class;
use function is_callable;

class EmsAsPsr implements MiddlewareInterface
{
    /**
     * @var callable|InputHandler
     */
    protected $emsMiddleware;

    /**
     * @param callable|InputHandler|null $emsMiddleware
     */
    public function __construct($emsMiddleware=null)
    {
        if($emsMiddleware) {
            $this->setEmsMiddleware($emsMiddleware);
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $nextHandler = function ($request) use ($handler) {
            $handler->handle($request);
        };
        if ($this->emsMiddleware instanceof InputHandler) {
            return $this->emsMiddleware->__invoke($request);
        }
        return call_user_func($this->emsMiddleware, $request, $nextHandler);
    }

    /**
     * @return callable|InputHandler|null
     */
    public function getEmsMiddleware()
    {
        return $this->emsMiddleware;
    }

    public function setEmsMiddleware($emsMiddleware) : EmsAsPsr
    {
        if (!is_callable($emsMiddleware) && !$emsMiddleware instanceof InputHandler) {
            throw new TypeError('Middleware must be callable or ' . InputHandler::class . ' not ' . get_class($emsMiddleware));
        }
        $this->emsMiddleware = $emsMiddleware;
        return $this;
    }
}