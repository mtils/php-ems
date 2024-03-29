<?php
/**
 *  * Created by mtils on 28.11.2021 at 18:33.
 **/

namespace Ems\Routing;

use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Ems\Contracts\Routing\UtilizesInput;
use Ems\Contracts\View\View as ViewContract;
use Ems\Core\Response;
use Ems\Http\Client;
use Ems\Http\HttpResponse;
use Ems\View\View;
use Ems\Contracts\Routing\UrlGenerator as UrlGeneratorContract;

use function strpos;

class ResponseFactory implements ResponseFactoryContract, UtilizesInput
{
    /**
     * @var Input
     */
    private $input;

    /**
     * @var UrlGeneratorContract
     */
    private $urls;

    public function __construct(UrlGeneratorContract $urls)
    {
        $this->urls = $urls;
    }

    /**
     * @param Stringable|string $content
     * @return Response|HttpResponse
     */
    public function create($content): Response
    {
        $attributes = ['payload' => $content];

        if ($content instanceof ViewContract && $content->mimeType()) {
            $attributes['contentType'] = $content->mimeType();
        }

        if ($this->input && $this->input->getClientType() == Input::CLIENT_CONSOLE) {
            return new Response($attributes);
        }

        $response = new HttpResponse($attributes);
        $response->provideSerializerBy(function ($contentType) {
            // Defer including of client because it is unlikely we need a
            // deserializer in self created responses
            $provider = Client::makeSerializerFactory();
            return $provider($contentType);
        });
        return $response;
    }

    /**
     * @param string $name
     * @param array $data
     * @return Response|HttpResponse
     */
    public function view(string $name, array $data = []): Response
    {
        $view = (new View($name))->assign($data);
        if ($this->input) {
            $view->setMimeType($this->input->getDeterminedContentType());
        }
        return $this->create($view);
    }

    /**
     * @param Url|string $to
     * @param array $routeParams
     * @return Response
     */
    public function redirect($to, array $routeParams = []): Response
    {
        $to = $this->isRouteName($to) ? $this->urls->route($to) : $to;
        return new HttpResponse([],['Location' => "$to"], 302);
    }

    public function setInput(Input $input)
    {
        $this->input = $input;
        $this->urls = $this->urls->withInput($input);
    }

    /**
     * Check if the passed $to is a route name.
     *
     * @param string|Url $to
     * @return bool
     */
    protected function isRouteName($to) : bool
    {
        if ($to instanceof Url) {
            return false;
        }
        return strpos($to, '/') === false;
    }
}