<?php
/**
 *  * Created by mtils on 25.12.2021 at 09:20.
 **/

namespace Ems\Core;

use Ems\Contracts\Core\Message;
use Ems\Contracts\Core\Stringable;
use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ResponseTest extends TestCase
{
    #[Test] public function it_implements_interfaces()
    {
        $response = $this->response();
        $this->assertInstanceOf(ImmutableMessage::class, $response);
        $this->assertInstanceOf(Stringable::class, $response);
    }

    #[Test] public function construct_applies_properties_and_attributes()
    {
        $attributes = [
            'type' => Message::TYPE_OUTPUT,
            'transport' => Message::TRANSPORT_NETWORK,
            'custom'    => ['foo' => 'bar'],
            'envelope'  => ['Content-Type' =>  'application/json'],
            'payload'   => 'blob'
        ];
        $response = $this->response($attributes);

        $this->assertEquals($attributes['type'], $response->type);
        $this->assertEquals($attributes['transport'], $response->transport);
        $this->assertEquals($attributes['custom'], $response->custom);
        $this->assertEquals($attributes['envelope'], $response->envelope);
        $this->assertEquals($attributes['payload'], $response->payload);
        $this->assertSame(0, $response->status);
        $this->assertSame('', $response->statusMessage);

    }

    #[Test] public function construct_applies_payload_if_only_one_parameter()
    {
        $response = $this->response('blob');
        $this->assertEquals('blob', $response->payload);

        $response = $this->response(['a','b','c']);
        $this->assertEquals(['a','b','c'], $response->payload);

    }

    #[Test] public function construct_applies_all_passed_parameters()
    {
        $response = $this->response(['foo' => 'bar'], ['type'=>'console'],-1);
        $this->assertEquals(['foo' => 'bar'], $response->payload);
        $this->assertEquals(['foo' => 'bar'], $response->custom);
        $this->assertEquals(['type' => 'console'], $response->envelope);
        $this->assertEquals(-1, $response->status);
        $this->assertEquals('bar', $response['foo']);

    }

    #[Test] public function withStatus_changes_status()
    {
        $response = $this->response('', [], 12);
        $this->assertEquals(12, $response->status);
        $this->assertSame('', $response->payload);
        $this->assertSame('', $response->statusMessage);

        $fork = $response->withStatus(0);
        $this->assertNotSame($response, $fork);
        $this->assertEquals(0, $fork->status);
        $this->assertSame('', $fork->statusMessage);

        $fork2 = $fork->withStatus(404, 'Not found');
        $this->assertNotSame($fork, $fork2);
        $this->assertEquals(404, $fork2->status);
        $this->assertSame('Not found', $fork2->statusMessage);

    }

    #[Test] public function withContentType_changes_contentType()
    {
        $response = $this->response(['contentType' => 'text/html']);
        $this->assertEquals('text/html', $response->contentType);

        $fork = $response->withContentType('application/json');
        $this->assertNotSame($response, $fork);
        $this->assertEquals('application/json', $fork->contentType);
    }

    #[Test] public function toString_creates_string_from_payload()
    {
        $this->assertEquals('blob', (string)$this->response('blob'));
        $urlString = 'https://web-utils.de';
        $url = new Url($urlString);
        $this->assertEquals($urlString, (string)$this->response($url));
    }

    protected function response(...$args) : Response
    {
        return new Response(...$args);
    }
}