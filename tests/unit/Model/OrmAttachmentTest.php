<?php
/**
 *  * Created by mtils on 17.12.17 at 15:49.
 **/

namespace Ems\Model;


use Ems\Contracts\Core\Containers\Size;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Url;
use Ems\Contracts\Model\Attachment;
use Ems\TestCase;

class OrmAttachmentTest extends TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(Attachment::class, $this->attachment());
    }

    public function test_getName()
    {
        $this->assertEquals('joe', $this->attachment(['name' => 'joe'])->getName());
    }

    public function test_resourceName()
    {
        $this->assertEquals('attachments', $this->attachment()->resourceName());
    }

    public function test_getUrl()
    {
        $path = 'https://foo.org/img.gif';
        $attachment = $this->attachment(['url' => $path]);
        $url = $attachment->getUrl();

        $this->assertInstanceOf(UrlContract::class, $url);
        $this->assertEquals($path, (string)$url);

        $path2 = 'https://foo.org/img.png';
        $attachment->url = $path2;

        $this->assertInstanceOf(UrlContract::class, $attachment->getUrl());
        $this->assertEquals($path2, (string)$attachment->getUrl());
    }

    public function test_getMimetype()
    {
        $type = 'image/png';
        $this->assertEquals($type, $this->attachment(['mimetype' => $type])->getMimetype());
    }

    public function test_getRole()
    {
        $role = Attachment::ALTERNATE_VIEW;
        $attachment = $this->attachment();
        $this->assertEquals(Attachment::DISPLAY, $attachment->getRole());
        $attachment->role = $role;
        $this->assertEquals($role, $attachment->getRole());

    }

    public function test_is()
    {
        $type = 'image/png';
        $attachment = $this->attachment(['mimetype' => $type]);
        $this->assertTrue($attachment->is('image/png'));
        $this->assertTrue($attachment->is('image'));
        $this->assertFalse($attachment->is('png'));
        $this->assertFalse($attachment->is('video'));
    }

    public function test_getSize()
    {
        $size = new Size(800,600);
        $attachment = $this->attachment(['width' => $size->width(), 'height' => $size->height()]);

        $this->assertTrue($size->equals($attachment->getSize()));
        // Tested caching in coverage
        $this->assertTrue($size->equals($attachment->getSize()));

        $newSize = new Size(640,480);
        $attachment->width = $newSize->width();
        $attachment->height = $newSize->height();

        $this->assertTrue($newSize->equals($attachment->getSize()));

    }

    public function test_addSize()
    {
        $baseUrl = new Url('https://web-utils.de');
        $attachment = $this->attachment([
            'id' => 1,
            'width' => 400,
            'height' => 300,
            'url' => $baseUrl->append('image-400x300.png')->toString()
        ]);

        $url = $attachment->getUrl();
        $this->assertEquals((string)$attachment->getUrl(), "$url");

        $url = $attachment->getUrl(800,600);
        $this->assertEquals((string)$attachment->getUrl(), "$url");


        $this->assertCount(1, $attachment->sizes());

        $urls = [];
        $dimensions = ['800x600', '250x200', '1024x768'];
        $sizes = [];

        foreach ($dimensions as $dimension) {
            $urls[$dimension] = $baseUrl->append("image-$dimension.png");
            list($width, $height) = explode('x', $dimension);
            $sizes[$dimension] = new Size($width, $height);
            $this->assertSame($attachment, $attachment->addSize($sizes[$dimension], $urls[$dimension]));
        }


        $this->assertCount(4, $attachment->sizes());

        // Adding same size does nothing
        $attachment->addSize([400,300], $baseUrl->append('image-400x300.png'));

        $this->assertCount(4, $attachment->sizes());

        $url = $attachment->getUrl(800,600);
        $this->assertTrue($urls['800x600']->equals($url));

        $url = $attachment->getUrl(740,400);
        $this->assertTrue($urls['800x600']->equals($url));

        $url = $attachment->getUrl(1920,1080);
        $this->assertTrue($urls['1024x768']->equals($url));

        $url = $attachment->getUrl(400,300);
        $this->assertTrue($attachment->getUrl()->equals($url));

        $url = $attachment->getUrl(400,300);
        $this->assertTrue($attachment->getUrl()->equals($url));

        $this->assertSame($attachment, $attachment->removeSize([800,600]));

        $this->assertCount(3, $attachment->sizes());
    }

    protected function attachment(array $attributes=[], $isFromStorage=true)
    {
        return new OrmAttachment($attributes, $isFromStorage);
    }
}