<?php
/**
 *  * Created by mtils on 13.01.2022 at 21:29.
 **/

namespace Ems\Contracts\Http;

use DateTime;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\TestCase;
use OutOfBoundsException;
use stdClass;
use UnexpectedValueException;

class CookieTest extends TestCase
{
    /**
     * @test
     */
    public function it_instantiates()
    {
        $this->assertInstanceOf(Cookie::class, $this->cookie('foo', 'bar'));
    }

    /**
     * @test
     */
    public function set_name_and_value()
    {
        $cookie = $this->cookie('foo', 'bar');
        $this->assertEquals('foo', $cookie->name);
        $this->assertEquals('bar', $cookie->value);
    }

    /**
     * @test
     */
    public function set_expire()
    {
        $expire = new DateTime('2022-01-13 21:45:17');
        $cookie = $this->cookie('foo', 'bar', $expire);
        $this->assertSame($expire, $cookie->expire);

        $cookie->expire = 90;
        $this->assertInstanceOf(DateTime::class, $cookie->expire);
        $this->assertGreaterThan($expire, $cookie->expire);

        $this->expectException(UnexpectedValueException::class);
        $cookie->expire = new stdClass();
    }

    /**
     * @test
     */
    public function set_path()
    {

        $this->assertEquals('/', $this->cookie('foo', 'bar')->path);
        $expire = new DateTime('2022-01-13 21:45:17');
        $cookie = $this->cookie('foo', 'bar', $expire, '/my/account');
        $this->assertEquals('/my/account', $cookie->path);
        $cookie->path = '/profile';
        $this->assertEquals('/profile', $cookie->path);
    }

    /**
     * @test
     */
    public function set_domain()
    {
        $this->assertSame('', $this->cookie('foo', 'bar')->domain);
        $expire = new DateTime('2022-01-13 21:45:17');
        $cookie = $this->cookie('foo', 'bar', $expire, '/', 'web-utils.de');
        $this->assertEquals('web-utils.de', $cookie->domain);
    }

    /**
     * @test
     */
    public function set_secure()
    {
        Cookie::$defaultSecure = false;
        $cookie = $this->cookie('foo','bar');
        $this->assertFalse($cookie->secure);
        Cookie::$defaultSecure = true;

        $cookie = $this->cookie('foo','bar');
        $this->assertTrue($cookie->secure);

        $cookie = $this->cookie('foo','bar', 0, '/', 'https://web-utils.de', false);
        $this->assertFalse($cookie->secure);

        $cookie = $this->cookie('foo','bar', 0, '/', 'https://web-utils.de', true);
        $this->assertTrue($cookie->secure);
    }

    /**
     * @test
     */
    public function set_httpOnly()
    {
        $default = Cookie::$defaultHttpOnly;
        $this->assertSame($default, $this->cookie('foo', 'bar')->httpOnly);

        Cookie::$defaultHttpOnly = !$default;
        $this->assertSame(!$default, $this->cookie('foo', 'bar')->httpOnly);

        Cookie::$defaultHttpOnly = $default;

        $test = !$default;
        $this->assertSame($test, $this->cookie('foo', 'bar', 0, '/', '', true, $test)->httpOnly);

        $cookie = $this->cookie('foo', 'bar');
        $cookie->httpOnly = false;
        $this->assertEquals(false, $cookie->httpOnly);

        $this->expectException(UnexpectedValueException::class);
        $cookie->httpOnly = 5;

    }

    /**
     * @test
     */
    public function set_sameSite()
    {
        $default = Cookie::$defaultSameSite;
        $this->assertSame($default, $this->cookie('foo', 'bar')->sameSite);
        Cookie::$defaultSameSite = Cookie::STRICT;
        $this->assertSame(Cookie::STRICT, $this->cookie('foo', 'bar')->sameSite);
        Cookie::$defaultSameSite = $default;

        $this->assertSame(Cookie::NONE, $this->cookie('foo', 'bar', 0, '/', '', true, true, Cookie::NONE)->sameSite);

        $cookie = $this->cookie('foo', 'bar');
        $cookie->sameSite = Cookie::STRICT;
        $this->assertEquals(Cookie::STRICT, $cookie->sameSite);

        $this->expectException(UnexpectedValueException::class);
        $cookie->sameSite = 'foo';
    }

    /**
     * @test
     */
    public function get_unknown_property_throws_exception()
    {
        $this->expectException(OutOfBoundsException::class);
        $this->cookie('foo', 'bar')->foo;
    }

    /**
     * @test
     */
    public function set_unknown_property_throws_exception()
    {
        $this->expectException(OutOfBoundsException::class);
        $cookie = $this->cookie('foo', 'bar');
        $cookie->foo = 'bar';
    }

    /**
     * @test
     */
    public function toString_returns_value()
    {
        $cookie = $this->cookie('foo', 'bar');
        $this->assertEquals('bar', "$cookie");
    }

    protected function cookie(...$args) : Cookie
    {
        return new Cookie(...$args);
    }
}
