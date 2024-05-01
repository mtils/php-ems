<?php
/**
 *  * Created by mtils on 17.12.17 at 12:02.
 **/

namespace Ems\Contracts\Core\Containers;


use Ems\TestCase;

class SizeTest extends TestCase
{
    public function test_new_instance()
    {
        $this->assertInstanceOf(Size::class,  $this->newSize());
    }

    public function test_width_and_height()
    {
        $size = $this->newSize(800,600);
        $this->assertEquals(800, $size->width());
        $this->assertEquals(600, $size->height());
        $this->assertSame($size, $size->setWidth(1024));
        $this->assertSame($size, $size->setHeight(768));
        $this->assertEquals(1024, $size->width());
        $this->assertEquals(768, $size->height());

        $size->setWidth(null);
        $this->assertNull($size->width());
    }

    public function test_aspectRatio()
    {
        $this->assertEquals(4, $this->newSize(1000, 250)->aspectRatio());
    }

    public function test_isLandscape()
    {
        $this->assertTrue($this->newSize(800, 600)->isLandscape());
        $this->assertFalse($this->newSize(800, 800)->isLandscape());
        $this->assertFalse($this->newSize(800, 801)->isLandscape());
        $this->assertFalse($this->newSize(600, 800)->isLandscape());
        $this->assertFalse($this->newSize(0, 800)->isLandscape());
        $this->assertFalse($this->newSize(800, 0)->isLandscape());
        $this->assertFalse($this->newSize(0, 0)->isLandscape());
    }

    public function test_isPortrait()
    {
        $this->assertFalse($this->newSize(800, 600)->isPortrait());
        $this->assertFalse($this->newSize(800, 800)->isPortrait());
        $this->assertTrue($this->newSize(800, 801)->isPortrait());
        $this->assertTrue($this->newSize(600, 800)->isPortrait());
        $this->assertFalse($this->newSize(0, 800)->isPortrait());
        $this->assertFalse($this->newSize(800, 0)->isPortrait());
        $this->assertFalse($this->newSize(0, 0)->isPortrait());
    }

    public function test_isSquare()
    {
        $this->assertFalse($this->newSize(800, 600)->isSquare());
        $this->assertTrue($this->newSize(800, 800)->isSquare());
        $this->assertFalse($this->newSize(800, 801)->isSquare());
        $this->assertFalse($this->newSize(600, 800)->isSquare());
        $this->assertFalse($this->newSize(0, 800)->isSquare());
        $this->assertFalse($this->newSize(800, 0)->isSquare());
        $this->assertFalse($this->newSize(0, 0)->isSquare());
    }

    public function test_total_returns_area()
    {
        $size = $this->newSize(800,600);
        $this->assertEquals(480000, $size->area());
        $this->assertEquals(480000, $size->total());
    }

    public function test_scale_scales_by_factor()
    {
        $size = $this->newSize(800,600);
        $scaled = $size->scale(2);
        $this->assertNotSame($size, $scaled);
        $this->assertEquals(1600, $scaled->width());
        $this->assertEquals(1200, $scaled->height());
        $this->assertEquals(800, $size->width());
        $this->assertEquals(600, $size->height());

    }

    public function test_scale_scales_by_newSize()
    {
        $size = $this->newSize(800,600);

        $toSize = $this->newSize(1076, 768);

        $scaled = $size->scale($toSize);

        $this->assertNotSame($size, $scaled);
        $this->assertNotSame($toSize, $scaled);

        $this->assertEquals(1076, $scaled->width());
        $this->assertEquals(768, $scaled->height());
        $this->assertEquals(800, $size->width());
        $this->assertEquals(600, $size->height());

    }

    public function test_scaleTo_throws_exception_if_no_width_and_hight_passed()
    {
        $this->expectException(\UnderflowException::class);
        $this->newSize(800,600)->scaleTo();
    }

    public function test_scaleTo_scales_exactly_if_width_and_height_are_passed()
    {
        $size = $this->newSize(800,600);
        $scaled = $size->scaleTo(1200, 800);

        $this->assertNotSame($size, $scaled);
        $this->assertEquals(1200, $scaled->width());
        $this->assertEquals(800, $scaled->height());

        $scaled = $size->scaleTo($this->newSize(1200, 800));

        $this->assertNotSame($size, $scaled);
        $this->assertEquals(1200, $scaled->width());
        $this->assertEquals(800, $scaled->height());

    }

    public function test_scaleTo_scales_width_by_keeping_aspect_ratio()
    {
        $size = $this->newSize(800,600);
        $scaled = $size->scaleTo(1200);

        $this->assertNotSame($size, $scaled);
        $this->assertEquals(1200, $scaled->width());
        $this->assertEquals($size->aspectRatio(), $scaled->aspectRatio());
    }

    public function test_scaleTo_scales_height_by_keeping_aspect_ratio()
    {
        $size = $this->newSize(800,600);
        $scaled = $size->scaleTo(null, 640);

        $this->assertNotSame($size, $scaled);
        $this->assertEquals(640, $scaled->height());
        $this->assertEquals($size->aspectRatio(), $scaled->aspectRatio());
    }

    public function test_fitInto_scales_not_bigger_than_target()
    {
        $size = $this->newSize(800,600);
        $scaled = $size->fitInto(640, 480);

        $this->assertNotSame($size, $scaled);
        $this->assertEquals(640, $scaled->width());
        $this->assertEquals(480, $scaled->height());

        $scaled = $size->fitInto(1024, 0);
        $this->assertFalse($scaled->isValid());

        $target = $this->newSize(1920, 1080);

        $scaled = $size->fitInto($target);
        $this->assertEquals($size->aspectRatio(), $scaled->aspectRatio());
        $this->assertNotBigger($target, $scaled);
        $this->assertOneEdgeMatches($target, $scaled);

        $target = $this->newSize(1080, 1920);

        $scaled = $size->fitInto($target);
        $this->assertEquals($size->aspectRatio(), $scaled->aspectRatio());
        $this->assertNotBigger($target, $scaled);
        $this->assertOneEdgeMatches($target, $scaled);
    }

    public function test_expandInto_scales_not_bigger_than_target()
    {
        $size = $this->newSize(800,600);
        $scaled = $size->expandTo(640, 480);

        $this->assertNotSame($size, $scaled);
        $this->assertEquals(640, $scaled->width());
        $this->assertEquals(480, $scaled->height());

        $scaled = $size->expandTo(1024, 0);
        $this->assertFalse($scaled->isValid());

        $target = $this->newSize(1920, 1080);

        $scaled = $size->expandTo($target);
        $this->assertEquals($size->aspectRatio(), $scaled->aspectRatio());
        $this->assertOneEdgeLonger($target, $scaled);
        $this->assertOneEdgeMatches($target, $scaled);

        $target = $this->newSize(1080, 1920);

        $scaled = $size->expandTo($target);
        $this->assertEquals($size->aspectRatio(), $scaled->aspectRatio());
        $this->assertOneEdgeLonger($target, $scaled);
        $this->assertOneEdgeMatches($target, $scaled);
    }

    public function test_multiply()
    {
        $size1 = $this->newSize(800,600);
        $size2 = $this->newSize(3,4);
        $result = $size1->multiply($size2);

        $this->assertEquals(2400, $result->width());
        $this->assertEquals(2400, $result->height());
    }

    public function test_add()
    {
        $size1 = $this->newSize(800,600);
        $size2 = $this->newSize(300,400);
        $result = $size1->add($size2);

        $this->assertEquals(1100, $result->width());
        $this->assertEquals(1000, $result->height());
    }

    public function test_subtract()
    {
        $size1 = $this->newSize(800,600);
        $size2 = $this->newSize(300,400);
        $result = $size1->subtract($size2);

        $this->assertEquals(500, $result->width());
        $this->assertEquals(200, $result->height());
    }

    public function test_divide()
    {
        $size1 = $this->newSize(800,600);
        $size2 = $this->newSize(2,3);
        $result = $size1->divide($size2);

        $this->assertEquals(400, $result->width());
        $this->assertEquals(200, $result->height());
    }

    public function test_findBest()
    {
        $sizes = [
            $this->newSize(800,600),
            $this->newSize(640,480),
            $this->newSize(3840,2160),
            $this->newSize(1024,768),
            $this->newSize(1920,1080),
            $this->newSize(1600,1200),
        ];

        $this->assertEquals(800, $this->newSize(700, 525)->findBest($sizes)->width());

        // Equals
        $this->assertEquals(1024, $this->newSize(1024, 768)->findBest($sizes)->width());

        $this->assertEquals(1600, $this->newSize(1200, 900)->findBest($sizes)->width());

        $this->assertEquals(3840, $this->newSize(7680, 4320)->findBest($sizes)->width());

        $one = [$this->newSize(640, 480)];
        $this->assertEquals(640, $this->newSize(7680, 4320)->findBest($one)->width());
    }

    public function test_findBest_throws_exception_if_sizes_empty()
    {
        $this->expectException(\UnderflowException::class);
        $this->newSize()->findBest([]);
    }

    protected function newSize($width=null, $height=null)
    {
        return new Size($width, $height);
    }

    protected function assertNotBigger(Size $size, Size $notBigger)
    {
        $this->assertTrue($notBigger->width() <= $size->width(), 'The scaled size should not be smaller.');
        $this->assertTrue($notBigger->height() <= $size->height(), 'The scaled size should not be smaller.');
    }

    protected function assertOneEdgeLonger(Size $size, Size $bigger)
    {
        if ($bigger->width() > $size->width()) {
            return;
        }

        if ($bigger->height() > $size->height()) {
            return;
        }


        $this->fail("Both edges of target size {$size->width()}x{$size->height()} are bigger than {$bigger->width()}x{$bigger->height()}");

    }

    protected function assertOneEdgeMatches(Size $target, Size $size)
    {
        if ($target->width() == $size->width()) {
            return;
        }

        if ($target->height() == $size->height()) {
            return;
        }


        $this->fail("One edge of {$target->width()}x{$target->height()} and {$size->width()}x{$size->height()} differ but at least one has to be equal.");

    }

}