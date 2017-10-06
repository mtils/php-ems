<?php

namespace Ems\Core;

use Ems\Testing\LoggingCallable;

class ManualMimeTypeProviderTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Core\MimeTypeProvider',
            $this->newProvider()
        );
    }

    public function test_returns_png_type_by_extension()
    {
        $this->assertType('image/png', 'png');
    }

    public function test_do_stupid_comparison_to_get_test_coverage()
    {
        $this->assertFileType('image/png', 'png');
    }

    public function test_returns_png_type_by_filename()
    {
        $this->assertType('image/png', 'image.png');
    }

    public function test_returns_png_type_by_absolute_path()
    {
        $this->assertType('image/png', '/srv/www/htdocts/my-domain/image.png');
    }

    public function test_throws_notfound_if_mimetype_not_found()
    {
        $this->assertType('', 'kmahjongg.kmgame');
    }

    public function test_first_hit_on_unknown_extension_triggers_callable()
    {
        $provider = $this->newProvider();
        $loader = new LoggingCallable();

        $provider->provideExtendedSet($loader);

        $this->assertEquals('application/json', $provider->typeOfName('json'));

        $this->assertCount(0, $loader);

        $this->assertEquals('', $provider->typeOfName('foo'));

        $this->assertCount(1, $loader);

        $this->assertSame($loader->arg(0), $provider);

        $this->assertEquals('', $provider->typeOfName('foo'));

        $this->assertCount(1, $loader);
    }

    public function test_isOfType_returns_true_on_direct_hit()
    {
        $this->assertIsOfType('foo.xml', 'application/xml');
    }

    public function test_isOfType_returns_false_on_unmatching_type()
    {
        $this->assertNotOfType('foo.zip', 'application/xml');
    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\NotFound
     **/
    public function test_isOfType_throws_not_found_if_mimetype_not_found()
    {
        $this->assertNotOfType('foo.kmgame', 'application/xml');
    }

    public function test_isOfType_returns_true_on_aliased_type()
    {
        $this->assertIsOfType('js', 'application/javascript');
        $this->assertIsOfType('js', 'text/javascript');
    }

    public function test_plus_sign_types_matches_to_non_plus_sign()
    {
        $provider = $this->newProvider();
        $provider->fillByArray(['application/vnd.api+json' => ['json-api']]);
        $this->assertTrue($provider->isOfType('users.json-api', 'application/json'));
    }

    public function test_fileExtensions_returns_mimeTypes_extensions()
    {

        $provider = $this->newProvider();
//         $provider->fillByArray(['image/jpeg' => ['json-api']]);
        $this->assertEquals(['jpeg', 'jpg', 'jpe'], $provider->fileExtensions('image/jpeg'));

    }

    public function test_fileExtensions_returns_mimeTypes_extensions_of_extended_set()
    {

        $provider = $this->newProvider();
        $provider->provideExtendedSet(function ($provider) {
            $provider->fillByArray([
                'application/script' => ['py', 'c', 'cpp']
            ]);
        });
//         $provider->fillByArray(['image/jpeg' => ['json-api']]);
        $this->assertEquals(['py', 'c', 'cpp'], $provider->fileExtensions('application/script'));

    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\NotFound
     **/
    public function test_fileExtensions_throws_exception_if_mimetype_unknown()
    {

        $provider = $this->newProvider();
        $provider->fileExtensions('application/bitmap');

    }

    protected function assertIsOfType($fileName, $type)
    {
        $provider = $this->newProvider();
        return $this->assertTrue($provider->isOfType($fileName, $type));
    }

    protected function assertNotOfType($fileName, $type)
    {
        $provider = $this->newProvider();
        return $this->assertFalse($provider->isOfType($fileName, $type));
    }

    protected function assertType($awaited, $fileName)
    {
        $provider = $this->newProvider();
        return $this->assertEquals($awaited, $provider->typeOfName($fileName));
    }

    protected function assertFileType($awaited, $fileName)
    {
        $provider = $this->newProvider();
        return $this->assertEquals($awaited, $provider->typeOfFile($fileName));
    }

    protected function newProvider()
    {
        return new ManualMimeTypeProvider();
    }
}
