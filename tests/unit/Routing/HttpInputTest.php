<?php
/**
 *  * Created by mtils on 01.01.2022 at 10:35.
 **/

namespace Ems\Routing;

use Ems\Contracts\Routing\Input;
use Ems\Core\Url;
use Ems\Http\Psr\UploadedFile;
use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

use const UPLOAD_ERR_OK;

class HttpInputTest extends TestCase
{
    #[Test] public function it_implements_interfaces()
    {
        $input = $this->input();
        $this->assertInstanceOf(Input::class, $input);
        $this->assertInstanceOf(ServerRequestInterface::class, $input);
    }

    #[Test] public function instantiate_with_payload_and_headers()
    {
        $data = ['foo' => 'bar'];
        $headers = ['Accept' => '*'];
        $input = $this->input($data, $headers);
        $this->assertEquals($data, $input->custom);
        $this->assertEquals($headers, $input->headers);
    }

    #[Test] public function apply_all_constructor_args()
    {
        $payload = 'foo';
        $headers = [
            'Accept-Encoding' => 'gzip, deflate, br',
            'Cache-Control' => 'max-age=0'
        ];
        $query = [
            'get' => 'foo'
        ];
        $body = [
            'post' => 'bar'
        ];
        $cookie = [
            'session_id' => 'abcd'
        ];
        $files = [
            'userFile' => [
                'name' => 'blank.gif',
                'type' => 'image/gif',
                'size' => 12,
                'error' => UPLOAD_ERR_OK,
                'tmp_name'  => '/tmp/sdajoiwejawsk9898'
            ]
        ];
        $server = [
            'PATH' => '/usr/local/bin:/usr/bin'
        ];
        $custom = [
            'a' => 'b'
        ];
        $input = $this->input($payload, $headers, $query, $body, $cookie, $files, $server, $custom);

        $this->assertEquals($payload, $input->payload);
        $this->assertEquals($payload, (string)$input->body);
        $this->assertEquals($headers, $input->headers);
        $this->assertEquals($query, $input->query);
        $this->assertEquals($body, $input->bodyParams);
        $this->assertEquals($cookie, $input->cookie);

        $file = $input->files['userFile'];
        $this->assertInstanceOf(UploadedFile::class, $file);
        $userFile = $files['userFile'];
        $this->assertEquals($userFile['tmp_name'], $file->getStream()->url());
        $this->assertEquals($userFile['type'], $file->getClientMediaType());
        $this->assertEquals($userFile['name'], $file->getClientFilename());
        $this->assertEquals($userFile['size'], $file->getSize());
        $this->assertEquals($userFile['error'], $file->getError());

        $this->assertEquals($server, $input->server);
        $this->assertEquals($custom, $input->custom);

    }

    #[Test] public function withCookieParams_changes_cookie_parameters()
    {
        $payload = 'foo';
        $headers = [
            'Accept-Encoding' => 'gzip, deflate, br',
            'Cache-Control' => 'max-age=0'
        ];
        $query = [
            'get' => 'foo'
        ];
        $body = [
            'post' => 'bar'
        ];
        $cookie = [
            'session_id' => 'abcd'
        ];
        $files = [
            'userFile' => [
                'name' => 'blank.gif',
                'type' => 'image/gif',
                'size' => 12,
                'error' => UPLOAD_ERR_OK,
                'tmp_name'  => '/tmp/sdajoiwejawsk9898'
            ]
        ];
        $server = [
            'PATH' => '/usr/local/bin:/usr/bin'
        ];
        $custom = [
            'a' => 'b'
        ];
        $input = $this->input($payload, $headers, $query, $body, $cookie, $files, $server, $custom);

        $this->assertEquals($payload, $input->payload);
        $this->assertEquals($payload, (string)$input->body);
        $this->assertEquals($headers, $input->headers);
        $this->assertEquals($query, $input->query);
        $this->assertEquals($body, $input->bodyParams);
        $this->assertEquals($cookie, $input->cookie);

        $newCookieParams = ['session' => 'abcd', 'utm' => 1];
        $fork = $input->withCookieParams($newCookieParams);
        $this->assertNotSame($fork, $input);
        $this->assertEquals($newCookieParams, $fork->cookie);

    }

    #[Test] public function withQueryParams_changes_query_parameters()
    {
        $payload = 'foo';
        $headers = [
            'Accept-Encoding' => 'gzip, deflate, br',
            'Cache-Control' => 'max-age=0'
        ];
        $query = [
            'get' => 'foo'
        ];
        $body = [
            'post' => 'bar'
        ];
        $cookie = [
            'session_id' => 'abcd'
        ];
        $files = [
            'userFile' => [
                'name' => 'blank.gif',
                'type' => 'image/gif',
                'size' => 12,
                'error' => UPLOAD_ERR_OK,
                'tmp_name'  => '/tmp/sdajoiwejawsk9898'
            ]
        ];
        $server = [
            'PATH' => '/usr/local/bin:/usr/bin'
        ];
        $custom = [
            'a' => 'b'
        ];
        $input = $this->input($payload, $headers, $query, $body, $cookie, $files, $server, $custom);

        $this->assertEquals($payload, $input->payload);
        $this->assertEquals($payload, (string)$input->body);
        $this->assertEquals($headers, $input->headers);
        $this->assertEquals($query, $input->query);
        $this->assertEquals($body, $input->bodyParams);
        $this->assertEquals($cookie, $input->cookie);

        $newQueryParams = ['sort' => 'name:desc', 'page' => 1];
        $fork = $input->withQueryParams($newQueryParams);
        $this->assertNotSame($fork, $input);
        $this->assertEquals($newQueryParams, $fork->query);

    }

    #[Test] public function withParsedBody_changes_body_parameters()
    {
        $payload = 'foo';
        $headers = [
            'Accept-Encoding' => 'gzip, deflate, br',
            'Cache-Control' => 'max-age=0'
        ];
        $query = [
            'get' => 'foo'
        ];
        $body = [
            'post' => 'bar'
        ];
        $cookie = [
            'session_id' => 'abcd'
        ];
        $files = [
            'userFile' => [
                'name' => 'blank.gif',
                'type' => 'image/gif',
                'size' => 12,
                'error' => UPLOAD_ERR_OK,
                'tmp_name'  => '/tmp/sdajoiwejawsk9898'
            ]
        ];
        $server = [
            'PATH' => '/usr/local/bin:/usr/bin'
        ];
        $custom = [
            'a' => 'b'
        ];
        $input = $this->input($payload, $headers, $query, $body, $cookie, $files, $server, $custom);

        $this->assertEquals($payload, $input->payload);
        $this->assertEquals($payload, (string)$input->body);
        $this->assertEquals($headers, $input->headers);
        $this->assertEquals($query, $input->query);
        $this->assertEquals($body, $input->bodyParams);
        $this->assertEquals($cookie, $input->cookie);

        $newBodyParams = ['sort' => 'name:desc', 'page' => 1];
        $fork = $input->withParsedBody($newBodyParams);
        $this->assertNotSame($fork, $input);
        $this->assertEquals($newBodyParams, $fork->bodyParams);

    }

    #[Test] public function withUploadedFiles_changes_files()
    {
        $payload = 'foo';
        $headers = [
            'Accept-Encoding' => 'gzip, deflate, br',
            'Cache-Control' => 'max-age=0'
        ];
        $query = [
            'get' => 'foo'
        ];
        $body = [
            'post' => 'bar'
        ];
        $cookie = [
            'session_id' => 'abcd'
        ];
        $files = [
            'userFile' => [
                'name' => 'blank.gif',
                'type' => 'image/gif',
                'size' => 12,
                'error' => UPLOAD_ERR_OK,
                'tmp_name'  => '/tmp/sdajoiwejawsk9898'
            ]
        ];

        $files2 = [
            'userFile2' => [
                'name' => 'blank2.png',
                'type' => 'image/png',
                'size' => 128,
                'error' => UPLOAD_ERR_OK,
                'tmp_name'  => '/tmp/shjasdhuejbdau8hj'
            ]
        ];

        $server = [
            'PATH' => '/usr/local/bin:/usr/bin'
        ];
        $custom = [
            'a' => 'b'
        ];
        $input = $this->input($payload, $headers, $query, $body, $cookie, $files, $server, $custom);

        $this->assertEquals($payload, $input->payload);
        $this->assertEquals($payload, (string)$input->body);
        $this->assertEquals($headers, $input->headers);
        $this->assertEquals($query, $input->query);
        $this->assertEquals($body, $input->bodyParams);
        $this->assertEquals($cookie, $input->cookie);

        $file = $input->files['userFile'];
        $this->assertInstanceOf(UploadedFile::class, $file);
        $userFile = $files['userFile'];
        $this->assertEquals($userFile['tmp_name'], $file->getStream()->url());
        $this->assertEquals($userFile['type'], $file->getClientMediaType());
        $this->assertEquals($userFile['name'], $file->getClientFilename());
        $this->assertEquals($userFile['size'], $file->getSize());
        $this->assertEquals($userFile['error'], $file->getError());

        $fork = $input->withUploadedFiles($files2);
        $this->assertNotSame($fork, $input);
        $file2 = $fork->getUploadedFiles()['userFile2'];
        $userFile2 = $files2['userFile2'];
        $this->assertEquals($userFile2['tmp_name'], $file2->getStream()->url());
        $this->assertEquals($userFile2['type'], $file2->getClientMediaType());
        $this->assertEquals($userFile2['name'], $file2->getClientFilename());
        $this->assertEquals($userFile2['size'], $file2->getSize());
        $this->assertEquals($userFile2['error'], $file2->getError());

    }

    #[Test] public function working_with_attributes()
    {
        $custom = ['foo' => 'bar'];
        $input = $this->input(['custom' => $custom]);

        $this->assertEquals($custom, $input->getAttributes());
        $this->assertEquals($custom['foo'], $input->getAttribute('foo'));

        $fork = $input->withAttribute('a', 'b');
        $this->assertEquals('b', $fork->getAttribute('a'));
        $fork2 = $fork->withoutAttribute('foo');
        $this->assertEquals(['a' => 'b'], $fork2->custom);

        $this->assertTrue($fork2->getAttribute('bla', true));

    }

    #[Test] public function withUrl_changes_url()
    {
        $url = new Url('https://web-utils.de/api/users');
        $input = $this->input($url);
        $this->assertSame($url, $input->uri);
        $this->assertSame($url, $input->url);
        $this->assertSame($url, $input->getUrl());
        $this->assertSame($url, $input->getUri());

        $url2 = new Url('https://web-utils.de/api/contacts?sort=updated');
        $fork = $input->withUri($url2);

        $this->assertSame($url2, $fork->uri);
        $this->assertSame($url2, $fork->url);
        $this->assertSame($url2, $fork->getUrl());
        $this->assertSame($url2, $fork->getUri());

        $url3 = new Url('https://web-utils.de/api/addresses');
        $fork2 = $fork->withUrl($url3);

        $this->assertSame($url3, $fork2->uri);
        $this->assertSame($url3, $fork2->url);
        $this->assertSame($url3, $fork2->getUrl());
        $this->assertSame($url3, $fork2->getUri());
    }

    #[Test] public function url_parameter_in_construct_works()
    {
        $url = new Url('https://web-utils.de/api/users');

        $input = $this->input(['url' => $url]);
        $this->assertSame($url, $input->uri);
        $this->assertSame($url, $input->url);
        $this->assertSame($url, $input->getUrl());
        $this->assertSame($url, $input->getUri());

        $url2 = $url->shift();
        $fork = $input->withClientType(Input::CLIENT_API)->withUri($url2);

        $this->assertSame($url2, $fork->getUrl());


    }

    #[Test] public function withClientType_changes_clientType()
    {
        $attributes = [
            'clientType' => Input::CLIENT_DESKTOP
        ];
        $input = $this->input($attributes);
        $this->assertEquals($attributes['clientType'], $input->clientType);
        $fork = $input->withClientType(Input::CLIENT_API);
        $this->assertNotSame($input, $fork);
        $this->assertEquals(Input::CLIENT_API, $fork->clientType);
    }

    #[Test] public function withApiVersion_changes_api_version()
    {
        $attributes = [
            'apiVersion' => '1.1'
        ];
        $input = $this->input($attributes);
        $this->assertEquals($attributes['apiVersion'], $input->apiVersion);
        $fork = $input->withApiVersion('1.2');
        $this->assertNotSame($input, $fork);
        $this->assertEquals('1.2', $fork->apiVersion);
    }

    #[Test] public function get_method_chooses_from_all_sources()
    {
        $payload = 'foo';
        $headers = [];
        $query = [
            'get' => 'foo'
        ];
        $body = [
            'post' => 'bar'
        ];
        $cookie = [];
        $files = [];
        $server = [];
        $custom = [
            'a' => 'b'
        ];
        $input = $this->input($payload, $headers, $query, $body, $cookie, $files, $server, $custom);

        $this->assertEquals('foo', $input->get('get'));
        $this->assertEquals('bar', $input->get('post'));
        $this->assertEquals('b', $input['a']);
        $this->assertSame(false, $input->get('foo', false));

    }

    #[Test] public function get_method_chooses_from_right_sources()
    {
        $payload = 'foo';
        $headers = [];
        $query = [
            'a' => 'a'
        ];
        $body = [
            'a' => 'b'
        ];
        $cookie = [];
        $files = [];
        $server = [];
        $custom = [];
        $input = $this->input($payload, $headers, $query, $body, $cookie, $files, $server, $custom);

        $this->assertEquals('a', $input->get('a'));

        $fork = $input->withQueryParams([]);
        $this->assertEquals('b', $fork->get('a'));

        $custom = [
            'a' => 'c'
        ];

        $fork2 = $fork->with($custom);
        $this->assertEquals($custom['a'], $fork2['a']);

    }

    #[Test] public function offsetExists_method_checks_all_sources()
    {
        $payload = 'foo';
        $headers = [];
        $query = [
            'get' => 'foo'
        ];
        $body = [
            'post' => 'bar',
        ];
        $cookie = [];
        $files = [];
        $server = [];
        $custom = [
            'a' => 'b'
        ];
        $input = $this->input($payload, $headers, $query, $body, $cookie, $files, $server, $custom);

        $this->assertTrue(isset($input['get']));
        $this->assertTrue(isset($input['post']));
        $this->assertTrue(isset($input['a']));
        $this->assertFalse(isset($input['foo']));

    }

    #[Test] public function toArray_merges_all_sources()
    {
        $payload = 'foo';
        $headers = [];
        $query = [
            'get' => 'foo'
        ];
        $body = [
            'post' => 'bar',
            'a'    => 'd'
        ];
        $cookie = [];
        $files = [];
        $server = [];
        $custom = [
            'a' => 'b'
        ];
        $input = $this->input($payload, $headers, $query, $body, $cookie, $files, $server, $custom);

        $array = [];
        foreach ($input as $key=>$value) {
            $array[$key] = $value;
        }
        $this->assertEquals([
            'a' => 'b',
            'get' => 'foo',
            'post' => 'bar'
                            ], $array);


    }

    protected function input(...$args)
    {
        return new HttpInput(...$args);
    }
}