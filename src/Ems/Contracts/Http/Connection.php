<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 18:44
 */

namespace Ems\Contracts\Http;

use Ems\Contracts\Core\Connection as ConnectionContract;
use Ems\Core\ImmutableMessage;
use Psr\Http\Message\ResponseInterface;

interface Connection extends ConnectionContract
{

    /**
     * @var string
     */
    const HEAD = 'HEAD';

    /**
     * @var string
     */
    const GET = 'GET';

    /**
     * @var string
     */
    const POST = 'POST';

    /**
     * @var string
     */
    const PUT = 'PUT';

    /**
     * @var string
     */
    const PATCH = 'PATCH';

    /**
     * @var string
     */
    const DELETE = 'DELETE';

    /**
     * Send an HTTP Request to a server. Get the raw response as answer.
     *
     * @param string $method
     * @param array  $headers (optional)
     * @param string $content (optional)
     * @param string $protocolVersion
     *
     * @return ImmutableMessage|ResponseInterface
     */
    public function send($method, array $headers=[], $content=null, $protocolVersion='1.1');
}