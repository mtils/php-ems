<?php

namespace Ems\Core;

use Ems\Contracts\Core\Url as UrlContract;

class UrlTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            UrlContract::class,
            $this->newUrl()
        );
    }

    public function test_path_leads_to_plain_path()
    {
        $path = '/home/marcus/test.txt';
        $url = $this->newUrl($path);
        $this->assertFalse($url->isRelative());
        $this->assertEquals($path, "$url");
    }

    public function test_path_with_scheme_leads_to_path()
    {
        $path = '/home/marcus/test.txt';
        $url = $this->newUrl($path);
        $newUrl = $url->scheme('file');
        $this->assertNotSame($url, $newUrl);
        $this->assertEquals("file://$path", "$newUrl");
        $this->assertEquals('file', $newUrl->scheme);
    }

    public function test_path_with_file_scheme_leads_to_path()
    {
        $path = 'file:///home/marcus/test.txt';
        $url = $this->newUrl($path);

        $this->assertEquals("$path", "$url");
        $this->assertEquals('/home/marcus/test.txt', "$url->path");
        $this->assertEquals('file', $url->scheme);
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_path_with_file_looking_scheme_throws_exception()
    {
        $path = "some-very-long-scheme-that-shouldnt-exist:///home/michael/file.txt";
        $url = $this->newUrl($path);

        $this->assertEquals("$path", "$url");
        $this->assertEquals('/home/marcus/test.txt', "$url->path");
        $this->assertEquals('file', $url->scheme);
    }

    public function test_path_with_non_file_scheme_leads_to_path()
    {
        $path = 'sqlite:///home/marcus/test.db';
        $url = $this->newUrl($path);

        $this->assertEquals("$path", "$url");
        $this->assertEquals('/home/marcus/test.db', "$url->path");
        $this->assertEquals('sqlite', $url->scheme);
    }

    public function test_user_adds_correct_syntax()
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

    public function test_user_and_password_adds_correct_syntax()
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

    public function test_user_adds_correct_syntax_on_flat_url()
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

    public function test_user_and_password_and_port_adds_correct_syntax()
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

    public function test_user_and_password_and_port_and_fragment_adds_correct_syntax()
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

    public function test_parses_and_renders_all_properties()
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


    public function test_path_sets_new_path()
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

    public function test_append_appends_path_segment()
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

    public function test_prepend_prepends_path_segment()
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

    public function test_pop_removes_lasts_path_segment()
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

    public function test_query_adds_query_param()
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

    public function test_query_adds_query_params()
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

    public function test_query_adds_query_params_by_querystring()
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

    public function test_query_replaces_query_params_by_querystring()
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

    public function test_isEmpty_returns_true_if_query_empty()
    {
        $this->assertTrue($this->newUrl()->isEmpty());
    }

    public function test_isEmpty_returns_false_if_query_empty()
    {
        $this->assertFalse($this->newUrl('http://google.de')->isEmpty());
        $this->assertFalse($this->newUrl('/home/michael')->isEmpty());
    }

    public function test_without_removes_one_query_keys()
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
     * @expectedException InvalidArgumentException
     **/
    public function test_get_throws_exception_if_property_unknown()
    {
        $this->newUrl()->foo;
    }

    public function test_offsetExists_returns_if_query_key_exists()
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

    public function test_offsetGet_returns_query_value()
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
     * @expectedException RuntimeException
     **/
    public function test_offsetSet_throws_exception()
    {
        $this->newUrl()['foo'] = 'bar';
    }

    /**
     * @expectedException RuntimeException
     **/
    public function test_offsetUnset_throws_exception()
    {
        $url = $this->newUrl();
        unset($url['foo']);
    }

    public function test_getIterator_iterates_over_query_items()
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

    public function test_passing_other_url_copies_it()
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
     * @expectedException InvalidArgumentException
     **/
    public function test_passing_unknown_object_throws_exception()
    {
        $this->newUrl(new \stdClass());
    }

    public function test_clear_path()
    {
        $path = '/home/marcus/test.txt';
        $url = $this->newUrl($path)->path('');
        $this->assertTrue($url->isRelative());
        $this->assertEquals('', (string)$url->path);
        $this->assertCount(0, $url->path);
    }

    public function test_relative_path()
    {
        $path = 'marcus/test.txt';
        $url = $this->newUrl($path);
        $this->assertTrue($url->isRelative());
        $this->assertEquals($path, (string)$url->path);
        $this->assertEquals($path, (string)$url);
    }

    public function test_clear_query()
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
     * @expectedException InvalidArgumentException
     **/
    public function test_query_throws_exception_if_type_unsupported()
    {
        $path = 'admin/session/create';
        $host = 'foo.de';
        $scheme = 'http';

        $url = $this->newUrl("$scheme://$host/$path");

        $url = $url->query(new \stdClass());
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_query_throws_exception_if_query_unparseable()
    {
        $url = $this->newUrl('http://');
    }

    public function test_appended_slash_behaviour()
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

    public function test_equals()
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

    public function newUrl($data=null)
    {
        return new Url($data);
    }

    protected function equals($url, $other, $parts=['scheme', 'user', 'password', 'host', 'path'])
    {
        return $this->newUrl($url)->equals($other, $parts);
    }
}
