<?php
/**
 *  * Created by mtils on 28.11.2021 at 18:33.
 **/

namespace Ems\Core;

use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\Input as InputContract;
use Ems\Contracts\Core\InputConnection;
use Ems\Contracts\Core\Response as ResponseContract;
use Ems\Contracts\Core\ResponseFactory as ResponseFactoryContract;
use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Core\UtilizesInput;
use Ems\Contracts\Routing\Routable;
use Ems\Core\Response as CoreResponse;
use Ems\Http\Response as HttpResponse;
use Ems\View\View;
use Ems\Contracts\View\View as ViewContract;

class ResponseFactory implements ResponseFactoryContract, UtilizesInput
{
    /**
     * @var InputConnection
     */
    private $connection;

    /**
     * @var InputContract
     */
    private $input;

    public function __construct(InputConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param Stringable|string $content
     * @return Response|HttpResponse
     */
    public function create($content): ResponseContract
    {
        if ($this->input->clientType() == Routable::CLIENT_CONSOLE) {
            return new CoreResponse($content);
        }
        $response = (new HttpResponse([]))->setPayload($content);
        if (!$content instanceof ViewContract) {
            return $response;
        }
        if ($content->mimeType()) {
            $response->setContentType($content->mimeType());
        }
        return $response;
    }

    /**
     * @param string $name
     * @param array $data
     * @return ResponseContract
     */
    public function view(string $name, array $data = []): ResponseContract
    {
        $view = (new View($name))->assign($data);
        $view->setMimeType($this->input->determinedContentType());
        return $this->create($view);
    }

    /**
     * @param Url|string $to
     * @param array $routeParams
     * @return ResponseContract
     */
    public function redirect($to, array $routeParams = []): ResponseContract
    {
        $response = new HttpResponse(['Location' => "$to"]);
        return $response->setStatus(302);
    }

    public function setInput(InputContract $input)
    {
        $this->input = $input;
    }


    /**
     * @return InputContract
     */
    protected function getInput() : InputContract
    {
        return $this->connection->read();
    }

}