<?php


namespace Ems\Core;


class AppPathTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Core\AppPath',
            $this->newAppPath()
        );
    }

    public function test_relative_returns_relative_on_absolute_path()
    {
        $basePath = '/srv/www/htdocs/app/public';
        $baseUrl = 'http://localhost';

        $relative = 'css/app.css';

        $appPath = $this->newAppPath($basePath, $baseUrl);

        $this->assertEquals($relative, $appPath->relative("$basePath/$relative"));

    }

    public function test_relative_returns_relative_on_relative_path()
    {
        $basePath = '/srv/www/htdocs/app/public';
        $baseUrl = 'http://localhost';

        $relative = 'css/app.css';

        $appPath = $this->newAppPath($basePath, $baseUrl);

        $this->assertEquals($relative, $appPath->relative($relative));

    }

    public function test_relative_returns_relative_on_url()
    {
        $basePath = '/srv/www/htdocs/app/public';
        $baseUrl = 'http://localhost';

        $relative = 'css/app.css';

        $appPath = $this->newAppPath($basePath, $baseUrl);

        $this->assertEquals($relative, $appPath->relative("$baseUrl/$relative"));

    }

    public function test_relative_returns_dot_on_empty_path_or_single_slash()
    {
        $basePath = '/srv/www/htdocs/app/public';
        $baseUrl = 'http://localhost';

        $relative = 'css/app.css';

        $appPath = $this->newAppPath($basePath, $baseUrl);

        $this->assertEquals('.', $appPath->relative(""));
        $this->assertEquals('.', $appPath->relative("/"));

    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_relative_throws_exception_on_positional_paths()
    {
        $basePath = '/srv/www/htdocs/app/public';
        $baseUrl = 'http://localhost';

        $relative = 'css/app.css';

        $appPath = $this->newAppPath($basePath, $baseUrl);

        $appPath->relative("../");

    }

    public function test_absolute_returns_absolute_on_absolute_path()
    {
        $basePath = '/srv/www/htdocs/app/public';
        $baseUrl = 'http://localhost';

        $relative = 'css/app.css';

        $appPath = $this->newAppPath($basePath, $baseUrl);

        $this->assertEquals("$basePath/$relative", $appPath->absolute("$basePath/$relative"));

    }

    public function test_toString_returns_basePath()
    {
        $basePath = '/srv/www/htdocs/app/public';
        $baseUrl = 'http://localhost';

        $relative = 'css/app.css';

        $appPath = $this->newAppPath($basePath, $baseUrl);

        $this->assertEquals($basePath, "$appPath");

    }

    public function test_absolute_returns_absolute_on_relative_path()
    {
        $basePath = '/srv/www/htdocs/app/public';
        $baseUrl = 'http://localhost';

        $relative = 'css/app.css';

        $appPath = $this->newAppPath($basePath, $baseUrl);

        $this->assertEquals("$basePath/$relative", $appPath->absolute("$relative"));

    }

    public function test_absolute_returns_basePath_if_empty_path_passed()
    {
        $basePath = '/srv/www/htdocs/app/public';
        $baseUrl = 'http://localhost';

        $relative = 'css/app.css';
        $absolutePath = "$basePath/$relative";

        $appPath = $this->newAppPath($basePath, $baseUrl);

        foreach (['.','/','',null] as $test) {
            $this->assertEquals($basePath, $appPath->absolute($test));
        }

    }

    public function test_absolute_returns_absolute_if_url_passed()
    {
        $basePath = '/srv/www/htdocs/app/public';
        $baseUrl = 'http://localhost';

        $relative = 'css/app.css';
        $absolutePath = "$basePath/$relative";

        $appPath = $this->newAppPath($basePath, $baseUrl);

        $this->assertEquals($absolutePath, $appPath->absolute("$baseUrl/$relative"));

    }

    public function test_getBasePath_getBaseUrl()
    {
        $basePath = '/srv/www/htdocs/app/public';
        $baseUrl = 'http://localhost';

        $appPath = $this->newAppPath($basePath, $baseUrl);

        $this->assertEquals($basePath, $appPath->getBasePath());
        $this->assertEquals($baseUrl, $appPath->getBaseUrl());

    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_setBaseUrl_with_empty_url_throws_InvalidArgumentException()
    {
        $basePath = '/srv/www/htdocs/app/public';

        $appPath = $this->newAppPath($basePath, '');

    }

    public function test_setBaseUrl_with_single_slash_doesnt_get_trimmed()
    {
        $basePath = '/srv/www/htdocs/app/public';

        $appPath = $this->newAppPath($basePath, '/');

        $this->assertEquals('/css', $appPath->url('css'));
        $this->assertEquals('/', $appPath->url());
        $this->assertEquals('.', $appPath->relative('/'));

    }

    public function test_url_returns_absolute_if_absolute_url_passed()
    {
        $basePath = '/srv/www/htdocs/app/public';
        $baseUrl = 'http://localhost';
        $urlAlias = '//localhost';

        $relative = 'css/app.css';
        $url = "$baseUrl/$relative";

        $appPath = $this->newAppPath($basePath, $baseUrl);

        $this->assertEquals($url, $appPath->url($url));
        $this->assertEquals("$urlAlias/$relative", $appPath->url("$urlAlias/$relative"));

    }

    public function test_url_returns_absolute_if_relative_url_passed()
    {
        $basePath = '/srv/www/htdocs/app/public';
        $baseUrl = 'http://localhost';

        $relative = 'css/app.css';
        $url = "$baseUrl/$relative";

        $appPath = $this->newAppPath($basePath, $baseUrl);

        $this->assertEquals($url, $appPath->url($relative));

    }

    public function test_url_returns_baseUrl_if_empty_path_passed()
    {
        $basePath = '/srv/www/htdocs/app/public';
        $baseUrl = 'http://localhost';

        $relative = 'css/app.css';
        $absolutePath = "$basePath/$relative";

        $appPath = $this->newAppPath($basePath, $baseUrl);

        foreach (['.','/','',null] as $test) {
            $this->assertEquals($baseUrl, $appPath->url($test));
        }

    }

    public function newAppPath($basePath='/srv', $baseUrl='http://localhost')
    {
        return (new AppPath)->setBasePath($basePath)
                            ->setBaseUrl($baseUrl);
    }

}
