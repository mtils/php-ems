<?php
/**
 *  * Created by mtils on 06.03.2022 at 09:00.
 **/

namespace Ems\Skeleton\Testing;

use Ems\Contracts\Routing\Input;
use Ems\Core\Response;
use Ems\Core\Url;
use Ems\Routing\GenericInput;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Routing\InputHandler;

trait HttpCalls
{
    protected function get($url, $parameters=[]) : Response
    {
        $url = $url instanceof UrlContract ? $url : $this->toUrl($url)->query($parameters);
        return $this->dispatch($this->request($url, '', Input::GET));
    }

    protected function post($url, $parameters=[]) : Response
    {
        return $this->dispatch($this->request($url, $parameters, Input::POST));
    }

    /**
     * Dispatch a request through the app
     * @param Input $input
     * @return Response
     */
    protected function dispatch(Input $input) : Response
    {
        /** @var callable $handler */
        $handler = $this->app()->get(InputHandler::class);
        return $handler($input);
    }

    /**
     * Create an http request.
     *
     * @param string|UrlContract    $url
     * @param mixed                 $payload
     * @param string                $method
     * @return GenericInput
     */
    protected function request($url, $payload='', string $method='') : Input
    {
        $input = new GenericInput($payload);
        if ($method) {
            $input->setMethod($method);
        }
        return $input->setUrl($this->toUrl($url));
    }

    protected function toUrl($url) : UrlContract
    {
        return $url instanceof UrlContract ? $url : (new Url($url))->host('localhost');
    }
}