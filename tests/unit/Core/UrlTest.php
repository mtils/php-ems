<?php

namespace Ems\Core;

use Ems\Contracts\Core\Url as UrlContract;
use Ems\TestCase;
use InvalidArgumentException;
use RuntimeException;
use stdClass;

use function rawurlencode;
use function str_replace;

class UrlTest extends TestCase
{
    /**
     * @test
     */
    public function implements_interface()
    {
        $this->assertInstanceOf(
            UrlContract::class,
            $this->newUrl()
        );
    }

    /**
     * @test
     */
    public function path_leads_to_plain_path()
    {
        $path = '/home/marcus/test.txt';
        $url = $this->newUrl($path);
        $this->assertFalse($url->isRelative());
        $this->assertEquals($path, "$url");
    }

    /**
     * @test
     */
    public function path_with_scheme_leads_to_path()
    {
        $path = '/home/marcus/test.txt';
        $url = $this->newUrl($path);
        $newUrl = $url->scheme('file');
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals("file://$path", "$newUrl");
        $this->assertEquals('file', $newUrl->scheme);
    }

    /**
     * @test
     */
    public function path_with_file_scheme_leads_to_path()
    {
        $path = 'file:///home/marcus/test.txt';
        $url = $this->newUrl($path);

        $this->assertEquals("$path", "$url");
        $this->assertEquals('/home/marcus/test.txt', "$url->path");
        $this->assertEquals('file', $url->scheme);
    }

    /**
     * @test
     */
    public function psr_scheme_methods()
    {
        $string = 'ftps://ftp.tils.org/public';
        $url = $this->newUrl($string);
        $this->assertEquals('ftps', $url->getScheme());
        $this->assertNotSame($url->withScheme('https'), $url);
        $this->assertEquals('https', $url->withScheme('https')->getScheme());
    }

    /**
     * @test
     */
    public function psr_host_methods()
    {
        $string = 'ftps://ftp.tils.org/public';
        $url = $this->newUrl($string);
        $this->assertEquals('ftp.tils.org', $url->getHost());
        $this->assertNotSame($url->withHost('web-utils.de'), $url);
        $this->assertEquals('web-utils.de', $url->withHost('web-utils.de')->getHost());
    }

    /**
     * @test
     */
    public function psr_post_methods()
    {
        $string = 'ftps://ftp.tils.org:55/public';
        $url = $this->newUrl($string);
        $this->assertEquals('55', $url->getPort());
        $this->assertNotSame($url->withPort(66), $url);
        $this->assertEquals('66', $url->withPort(66)->getPort());
    }

    /**
     * @test
     */
    public function psr_query_methods()
    {
        $string = 'https://ftp.tils.org:55/public?foo=bar&boing=whoops';
        $url = $this->newUrl($string);
        $this->assertEquals('foo=bar&boing=whoops', $url->getQuery());
        $this->assertNotSame($url->withQuery('foo=bar&boing=yippeah'), $url);
        $this->assertEquals('foo=bar&boing=yippeah', $url->withQuery('foo=bar&boing=yippeah')->getQuery());
        $this->assertSame('', $this->newUrl('https://ftp.tils.org:55/public')->getQuery());
    }

    /**
     * @test
     */
    public function psr_fragment()
    {
        $string = 'https://ftp.tils.org:55/public#top';
        $url = $this->newUrl($string);
        $this->assertEquals('top', $url->getFragment());
        $this->assertNotSame($url->withFragment('contact'), $url);
        $this->assertEquals('contact', $url->withFragment('contact')->getFragment());
    }

    /**
     * @test
     */
    public function psr_path_methods()
    {
        $string = 'ftps://ftp.tils.org/public/index.html';
        $url = $this->newUrl($string);
        $this->assertEquals('/public/index.html', $url->getPath());
        $this->assertNotSame($url->withPath('public/img/blank.gif'), $url);
        $this->assertEquals('/public/img/blank.gif', $url->withPath('public/img/blank.gif')->getPath());
    }

    /**
     * @test
     */
    public function path_with_file_looking_scheme_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $path = "some-very-long-scheme-that-shouldnt-exist:///home/michael/file.txt";
        $url = $this->newUrl($path);

        $this->assertEquals("$path", "$url");
        $this->assertEquals('/home/marcus/test.txt', "$url->path");
        $this->assertEquals('file', $url->scheme);
    }

    /**
     * @test
     */
    public function path_with_non_file_scheme_leads_to_path()
    {
        $path = 'sqlite:///home/marcus/test.db';
        $url = $this->newUrl($path);

        $this->assertEquals("$path", "$url");
        $this->assertEquals('/home/marcus/test.db', "$url->path");
        $this->assertEquals('sqlite', $url->scheme);
    }

    /**
     * @test
     */
    public function user_adds_correct_syntax()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $user = 'hannah';

        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url->user($user);
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals($user, $newUrl->user);
        $this->assertEquals($scheme, $newUrl->scheme);
        $this->assertEquals($host, $newUrl->host);
        $this->assertEquals("$scheme://$user@$host/$path", "$newUrl");
    }

    /**
     * @test
     */
    public function user_adds_correct_syntax_psr()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $user = 'hannah';

        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url->withUserInfo($user);
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals($user, $newUrl->user);
        $this->assertEquals($scheme, $newUrl->scheme);
        $this->assertEquals($host, $newUrl->host);
        $this->assertEquals("$scheme://$user@$host/$path", "$newUrl");
    }

    /**
     * @test
     */
    public function user_and_password_adds_correct_syntax()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $user = 'hannah';
        $password = '123';

        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url->user($user)->password($password);
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals($user, $newUrl->user);
        $this->assertEquals($scheme, $newUrl->scheme);
        $this->assertEquals($host, $newUrl->host);
        $this->assertEquals($password, $newUrl->password);
        $this->assertEquals("$scheme://$user:xxxxxx@$host/$path", "$newUrl");
    }

    /**
     * @test
     */
    public function user_and_password_adds_correct_syntax_psr()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $user = 'hannah';
        $password = '123';

        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url->withUserInfo($user, $password);
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals($user, $newUrl->user);
        $this->assertEquals($scheme, $newUrl->scheme);
        $this->assertEquals($host, $newUrl->host);
        $this->assertEquals($password, $newUrl->password);
        $this->assertEquals("$scheme://$user:xxxxxx@$host/$path", "$newUrl");
    }

    /**
     * @test
     */
    public function user_adds_correct_syntax_on_flat_url()
    {
        $host = 'foo.de';
        $scheme = 'mailto';
        $user = 'hannah';

        $url = $this->newUrl()
                    ->scheme($scheme)
                    ->host($host)
                    ->user($user);

        $this->assertEquals($user, $url->user);
        $this->assertEquals($scheme, $url->scheme);
        $this->assertEquals($host, $url->host);
        $this->assertEquals("$scheme:$user@$host", "$url");
    }

    /**
     * @test
     */
    public function user_and_password_and_port_adds_correct_syntax()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $user = 'hannah';
        $password = '123';
        $port = 88;

        $url = $this->newUrl("/$path");

        $newUrl = $url->scheme($scheme)->host($host)->port($port)->user($user)->password($password);
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals($user, $newUrl->user);
        $this->assertEquals($scheme, $newUrl->scheme);
        $this->assertEquals($host, $newUrl->host);
        $this->assertEquals($password, $newUrl->password);
        $this->assertEquals("$scheme://$user:xxxxxx@$host:$port/$path", "$newUrl");
    }

    /**
     * @test
     */
    public function user_and_password_and_port_and_fragment_adds_correct_syntax()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $user = 'hannah';
        $password = '123';
        $port = 88;
        $fragment = 'top';

        $url = $this->newUrl("/$path");

        $newUrl = $url->scheme($scheme)
                      ->host($host)
                      ->port($port)
                      ->user($user)
                      ->password($password)
                      ->fragment($fragment);
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals($user, $newUrl->user);
        $this->assertEquals($scheme, $newUrl->scheme);
        $this->assertEquals($host, $newUrl->host);
        $this->assertEquals($password, $newUrl->password);
        $this->assertEquals("$scheme://$user:xxxxxx@$host:$port/$path#$fragment", "$newUrl");
    }

    /**
     * @test
     */
    public function user_and_password_are_encoded()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $user = 'hannah@gmail.com';
        $password = '123';
        $port = 88;
        $fragment = 'top';

        $url = $this->newUrl("/$path");

        $newUrl = $url->scheme($scheme)
            ->host($host)
            ->port($port)
            ->user($user)
            ->password($password)
            ->fragment($fragment);
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals($user, $newUrl->user);
        $this->assertEquals($scheme, $newUrl->scheme);
        $this->assertEquals($host, $newUrl->host);
        $this->assertEquals($password, $newUrl->password);
        $encodedUser = rawurlencode($user);
        $this->assertEquals("$scheme://$encodedUser:xxxxxx@$host:$port/$path#$fragment", "$newUrl");
    }

    /**
     * @test
     */
    public function user_and_password_are_decoded()
    {
        $user = 'hannah@gmail.com';
        $password = 'password 123';

        $address = "http://hannah%40gmail.com:password%20123@foo.de:88/admin/session/create#top";
        $result = str_replace('password%20123', 'xxxxxx', $address);

        $url = $this->newUrl($address);
        $this->assertEquals($user, $url->user);
        $this->assertEquals($password, $url->password);
        $this->assertEquals($result, $url->toString());

    }

    /**
     * @test
     */
    public function parses_and_renders_all_properties()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $user = 'hannah';
        $password = '123';
        $port = 88;
        $fragment = 'top';

        $query = [
            'sessionId' => 'very-long-id',
            'name'      => 'uncle-sam'
        ];

        $queryPart = http_build_query($query);

        $string = "$scheme://$user:$password@$host:$port/$path?$queryPart#$fragment";

        $url = $this->newUrl($string);

        $this->assertEquals($scheme, $url->scheme);
        $this->assertEquals($user, $url->user);
        $this->assertEquals($password, $url->password);
        $this->assertEquals($host, $url->host);
        $this->assertEquals($port, $url->port);
        $this->assertEquals("/$path", (string)$url->path);
        $this->assertInstanceOf('Ems\Core\Collections\StringList', $url->path);
        $this->assertEquals($query, $url->query);
        $this->assertEquals($fragment, $url->fragment);
        $this->assertEquals($password, $url->password);

        $this->assertEquals(str_replace(':123',':xxxxxx', $string), "$url");
    }

    /**
     * @test
     */
    public function path_sets_new_path()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $newPath = 'home/personal-data';

        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url->path($newPath);
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals("/$path", (string)$url->path);
        $this->assertEquals("/$newPath", (string)$newUrl->path);
        $this->assertEquals("$scheme://$host/$newPath", "$newUrl");
    }

    /**
     * @test
     */
    public function append_appends_path_segment()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $segment = 'manual';

        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url->append($segment);
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals("/$path", (string)$url->path);
        $this->assertEquals("/$path/$segment", (string)$newUrl->path);
        $this->assertEquals("$scheme://$host/$path/$segment", "$newUrl");
    }

    /**
     * @test
     */
    public function prepend_prepends_path_segment()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $segment = 'de';

        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url->prepend($segment);
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals("/$path", (string)$url->path);
        $this->assertEquals("/$segment/$path", (string)$newUrl->path);
        $this->assertEquals("$scheme://$host/$segment/$path", "$newUrl");
    }

    /**
     * @test
     */
    public function pop_removes_lasts_path_segment()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';

        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url->pop();
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals("/$path", (string)$url->path);
        $this->assertEquals('/admin/session', (string)$newUrl->path);
        $this->assertEquals("$scheme://$host/admin/session", "$newUrl");
    }

    /**
     * @test
     */
    public function shift_removes_first_path_segment()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';

        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url->shift();
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals("/$path", (string)$url->path);
        $this->assertEquals('/session/create', (string)$newUrl->path);
        $this->assertEquals("$scheme://$host/session/create", "$newUrl");
    }

    /**
     * @test
     */
    public function shift_removes_first_path_segments()
    {
        $path = 'admin/users/144/addresses/104';
        $host = 'foo.de';
        $scheme = 'http';

        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url->shift(3);
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals("/$path", (string)$url->path);
        $this->assertEquals('/addresses/104', (string)$newUrl->path);
        $this->assertEquals("$scheme://$host/addresses/104", "$newUrl");
    }

    /**
     * @test
     */
    public function query_adds_query_param()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $query = [
            'id'   => 45,
            'name' => 'elsa-unicorn-power'
        ];

        $queryPart = http_build_query($query);


        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url;

        foreach ($query as $key=>$value) {
            $newUrl = $newUrl->query($key, $value);
        }


        $this->assertNotSame($url, $newUrl);
        $this->assertEquals($query, $newUrl->query);
        $this->assertEquals("$scheme://$host/$path?$queryPart", "$newUrl");
    }

    /**
     * @test
     */
    public function query_adds_query_params()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $query = [
            'id'   => 45,
            'name' => 'elsa-unicorn-power'
        ];

        $queryPart = http_build_query($query);


        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url->query($query);

        $this->assertNotSame($url, $newUrl);
        $this->assertEquals($query, $newUrl->query);
        $this->assertEquals("$scheme://$host/$path?$queryPart", "$newUrl");
    }

    /**
     * @test
     */
    public function query_adds_query_params_by_querystring()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $query = [
            'id'   => 45,
            'name' => 'elsa-unicorn-power'
        ];

        $queryPart = http_build_query($query);


        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url->query('id=45&name=elsa-unicorn-power');

        $this->assertNotSame($url, $newUrl);
        $this->assertEquals($query, $newUrl->query);
        $this->assertEquals("$scheme://$host/$path?$queryPart", "$newUrl");
    }

    /**
     * @test
     */
    public function query_replaces_query_params_by_querystring()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $query = [
            'id'   => 45,
            'name' => 'elsa-unicorn-power'
        ];

        $awaited = [
            'name' => 'elsa-unicorn-power'
        ];

        $queryPart = http_build_query($awaited);


        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url->query($query)->query('?name=elsa-unicorn-power');

        $this->assertNotSame($url, $newUrl);
        $this->assertEquals($awaited, $newUrl->query);
        $this->assertEquals("$scheme://$host/$path?$queryPart", "$newUrl");
    }

    /**
     * @test
     */
    public function isEmpty_returns_true_if_query_empty()
    {
        $this->assertTrue($this->newUrl()->isEmpty());
    }

    /**
     * @test
     */
    public function isEmpty_returns_false_if_query_empty()
    {
        $this->assertFalse($this->newUrl('http://google.de')->isEmpty());
        $this->assertFalse($this->newUrl('/home/michael')->isEmpty());
    }

    /**
     * @test
     */
    public function without_removes_one_query_keys()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $query = [
            'id'   => 45,
            'name' => 'elsa-unicorn-power',
            'age' => 56,
            'nick' => 'mustafa'
        ];

        $url = $this->newUrl("$scheme://$host/$path");

        $newUrl = $url->query($query);

        $this->assertNotSame($url, $newUrl);
        $this->assertEquals($query, $newUrl->query);

        $this->assertTrue(isset($newUrl->query['id']));

        $url2 = $newUrl->without('id');

        $this->assertNotSame($url2, $newUrl);

        foreach ($query as $key=>$value) {
            if ($key == 'id') {
                $this->assertFalse(isset($url2->query[$key]));
                continue;
            }
            $this->assertTrue(isset($url2->query[$key]));
        }

        $url3 = $newUrl->without('id', 'name', 'nick');

        foreach ($query as $key=>$value) {
            if ($key == 'age') {
                $this->assertTrue(isset($url3->query[$key]));
                continue;
            }
            $this->assertFalse(isset($url3->query[$key]));
        }

    }

    /**
     * @test
     */
    public function get_throws_exception_if_property_unknown()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->newUrl()->foo;
    }

    /**
     * @test
     */
    public function offsetExists_returns_if_query_key_exists()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';

        $url = $this->newUrl("$scheme://$host/$path");

        $url = $url->query('id=45&name=elsa-unicorn-power');

        $this->assertTrue(isset($url['id']));
        $this->assertTrue(isset($url['name']));
        $this->assertFalse(isset($url['foo']));
    }

    /**
     * @test
     */
    public function offsetGet_returns_query_value()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';

        $url = $this->newUrl("$scheme://$host/$path");

        $url = $url->query('id=45&name=elsa-unicorn-power');

        $this->assertEquals('45', $url['id']);
        $this->assertEquals('elsa-unicorn-power', $url['name']);
    }

    /**
     * @test
     */
    public function test_offsetSet_throws_exception()
    {
        $this->expectException(RuntimeException::class);
        $this->newUrl()['foo'] = 'bar';
    }

    /**
     * @test
     */
    public function test_offsetUnset_throws_exception()
    {
        $url = $this->newUrl();
        $this->expectException(RuntimeException::class);
        unset($url['foo']);
    }

    /**
     * @test
     */
    public function getIterator_iterates_over_query_items()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $query = [
            'id'   => 45,
            'name' => 'elsa-unicorn-power'
        ];


        $queryPart = http_build_query($query);


        $url = $this->newUrl("$scheme://$host/$path?$queryPart");

        $queryItems = [];

        foreach ($url as $key=>$value) {
            $queryItems[$key] = $value;
        }

        $this->assertEquals($query, $queryItems);
    }

    /**
     * @test
     */
    public function passing_other_url_copies_it()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';
        $user = 'hannah';
        $password = '123';
        $port = 88;
        $fragment = 'top';

        $query = [
            'sessionId' => 'very-long-id',
            'name'      => 'uncle-sam'
        ];

        $queryPart = http_build_query($query);

        $string = "$scheme://$user:$password@$host:$port/$path?$queryPart#$fragment";

        $url = $this->newUrl($string);
        $copy = $this->newUrl($url);

        $output = str_replace(':123',':xxxxxx', $string);
        $this->assertEquals($output, "$url");
        $this->assertEquals($output, "$copy");
        $this->assertEquals($password, $url->password);
        $this->assertEquals($password, $copy->password);
    }

    /**
     * @test
     */
    public function passing_unknown_object_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->newUrl(new stdClass());
    }

    /**
     * @test
     */
    public function clear_path()
    {
        $path = '/home/marcus/test.txt';
        $url = $this->newUrl($path)->path('');
        $this->assertTrue($url->isRelative());
        $this->assertEquals('', (string)$url->path);
        $this->assertCount(0, $url->path);
    }

    /**
     * @test
     */
    public function relative_path()
    {
        $path = 'marcus/test.txt';
        $url = $this->newUrl($path);
        $this->assertTrue($url->isRelative());
        $this->assertEquals($path, (string)$url->path);
        $this->assertEquals($path, (string)$url);
    }

    /**
     * @test
     */
    public function clear_query()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';

        $url = $this->newUrl("$scheme://$host/$path");

        $url = $url->query('id=45&name=elsa-unicorn-power');

        $this->assertEquals('45', $url['id']);
        $this->assertEquals('elsa-unicorn-power', $url['name']);

        $newUrl = $url->query('');
        $this->assertEquals([], $newUrl->query);
    }

    /**
     * @test
     */
    public function query_throws_exception_if_type_unsupported()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';

        $url = $this->newUrl("$scheme://$host/$path");

        $this->expectException(InvalidArgumentException::class);
        $url->query(new stdClass());
    }

    /**
     * @test
     */
    public function test_query_throws_exception_if_query_unparseable()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->newUrl('http://');
    }

    /**
     * @test
     */
    public function appended_slash_behaviour()
    {
        $tests = [
            'http://google.de'      => '',
            'https://google.de/'    => '/',
            'http://localhost'      => '',
            'http://localhost/'     => '/',
            'http://google.de/foo'  => '/foo',
            'http://google.de/foo/' => '/foo/',
            '/var/tmp/'             => '/var/tmp/',
            '/var/tmp'              => '/var/tmp',
            'file:///var/tmp/'      => '/var/tmp/',
            'file:///var/tmp'       => '/var/tmp',
            'file:///'              => '/',
            '/'                     => '/',
        ];

        foreach ($tests as $test=>$path) {
            $url = $this->newUrl($test);
            $this->assertEquals($test, "$url", "Slash suffix is wrong compared to $test");
            $this->assertEquals($path, "$url->path", "Path slash suffix '$url->path' of '$url' is wrong compared to '$path'");
        }

    }

    /**
     * @test
     */
    public function appended_slash_in_domain_does_not_lead_to_appended_slashes_in_path()
    {

        $url = $this->newUrl('https://google.com/');
        $url = $url->append('analytics');
        $this->assertEquals('https://google.com/analytics', "$url");
        $url = $url->append('graphs', 'line');
        $this->assertEquals('https://google.com/analytics/graphs/line', "$url");
    }

    /**
     * @test
     */
    public function appended_slash_in_domain_with_path_does_lead_to_appended_slashes_in_path()
    {

        $url = $this->newUrl('https://google.com/analytics/');
        $this->assertEquals('https://google.com/analytics/', "$url");
        $url = $url->append('graphs', 'line');
        $this->assertEquals('https://google.com/analytics/graphs/line/', "$url");
    }

    /**
     * @test
     */
    public function equals_compares()
    {
        $this->assertTrue($this->equals('/home/michi', '/home/michi'));
        $this->assertTrue($this->equals('http://www.ip.de', 'http://www.ip.de'));
        $this->assertFalse($this->equals('https://www.ip.de', 'http://www.ip.de'));
        $this->assertTrue($this->equals('https://www.ip.de', 'http://www.ip.de', ['host', 'path']));
        $this->assertTrue($this->equals('https://www.ip.de/users/14/address', 'http://www.ip.de/users/14/address', ['host', 'path']));
        $this->assertTrue($this->equals('https://www.ip.de/users/14/address', '/users/14/address', 'path'));
        $this->assertFalse($this->equals('https://www.ip.de/users/14/address', '/users/14/address', 'scheme'));
        $this->assertTrue($this->equals('https://www.ip.de/users/14/address#hihi', '/users/14/address#hihi', 'fragment'));

    }

    public function newUrl($data=null) : Url
    {
        return new Url($data);
    }

    protected function equals($url, $other, $parts=['scheme', 'user', 'password', 'host', 'path'])
    {
        return $this->newUrl($url)->equals($other, $parts);
    }
}
