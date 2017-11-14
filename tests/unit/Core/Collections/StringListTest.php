<?php 

namespace Ems\Core\Collections;

use Ems\Testing\LoggingCallable;

require_once __DIR__.'/OrderedListTest.php';

class StringListTest extends OrderedListTest
{
    public function test_implements_interface()
    {
        $this->assertInstanceof('ArrayAccess', $this->newList());
        $this->assertInstanceof('IteratorAggregate', $this->newList());
        $this->assertInstanceof('Countable', $this->newList());
    }

    public function test_getGlue_and_setGlue()
    {
        $list = $this->newList();
        $this->assertSame($list, $list->setGlue('.'));
        $this->assertEquals('.', $list->getGlue());
    }

    public function test_getPrefix_and_setPrefix()
    {
        $list = $this->newList();
        $this->assertSame($list, $list->setPrefix('Fruits: '));
        $this->assertEquals('Fruits: ', $list->getPrefix());
    }

    public function test_getSuffix_and_setSuffix()
    {
        $list = $this->newList();
        $this->assertSame($list, $list->setSuffix(' (healthy)'));
        $this->assertEquals(' (healthy)', $list->getSuffix());
    }

    public function test_construct_with_string_splits()
    {
        $this->assertEquals('abcdef', (string)$this->newList('abcdef'));
    }

    public function test_construct_with_char_creates_range()
    {
        $CHARS = range('A', 'Z');
        $chars = range('a', 'z');
        $STRING = implode($CHARS);
        $string = implode($chars);

        $this->assertEquals($CHARS, $this->newList()->setGlue('')->setSource($STRING)->getSource());
        $this->assertEquals($chars, $this->newList()->setGlue('')->setSource($string)->getSource());

    }

    public function test_equals_compares_with_string()
    {
        $this->assertTrue($this->equals('foo', 'foo'));
        $this->assertTrue($this->equals('foo/bar', 'foo/bar'));
        $this->assertTrue($this->equals('foo/bar/baz', 'foo/bar/baz'));
        $this->assertTrue($this->equals('foo/bar/baz', $this->newList('foo/bar/baz')));

    }

    public function test_equals_compares_affixes()
    {
        $this->assertTrue($this->path('foo')->equals('foo'));
        $this->assertTrue($this->path('foo/bar')->equals('foo/bar'));
        $this->assertTrue($this->path('foo', '/')->equals('foo'));


        $this->assertTrue($this->path('foo/bar')->equals('foo/bar'));
        $this->assertTrue($this->path('foo/bar', '/')->equals('foo/bar'));
        $this->assertTrue($this->path('foo/bar', '/', '/')->equals('foo/bar'));


        $this->assertTrue($this->path('/foo/bar')->equals('foo/bar'));
        $this->assertTrue($this->path('/foo/bar', '/')->equals('foo/bar'));
        $this->assertTrue($this->path('/foo/bar', '/', '/')->equals('foo/bar'));

        $this->assertTrue($this->path('/foo/bar')->equals('foo/bar/'));
        $this->assertTrue($this->path('/foo/bar', '/')->equals('foo/bar/'));
        $this->assertTrue($this->path('/foo/bar', '/', '/')->equals('foo/bar/'));

        $this->assertTrue($this->path('foo/bar')->equals('/foo/bar'));
        $this->assertTrue($this->path('foo/bar', '/')->equals('/foo/bar'));
        $this->assertTrue($this->path('foo/bar', '/', '/')->equals('/foo/bar'));

        $this->assertTrue($this->path('/foo/bar/')->equals('/foo/bar'));
        $this->assertTrue($this->path('/foo/bar/', '/')->equals('/foo/bar'));
        $this->assertTrue($this->path('/foo/bar/', '/', '/')->equals('/foo/bar'));

        $this->assertTrue($this->path('foo/bar')->equals('/foo/bar/'));
        $this->assertTrue($this->path('foo/bar', '/')->equals('/foo/bar/'));
        $this->assertTrue($this->path('foo/bar', '/', '/')->equals('/foo/bar/'));
        $this->assertFalse($this->path('foo/bar', '/')->equals('/foo/bar/', true));

    }

    public function test_construct_with_empty_string()
    {
        $this->assertEquals([], $this->newList('')->getSource());
    }


    protected function newList($params=null)
    {
        return new StringList($params);
    }

    protected function path($string, $prefix='', $suffix='')
    {
        return new StringList($string,'/', $prefix, $suffix);
    }

    /**
     * @param string $string
     * @param mixed  $other
     * @param bool   $strict
     * @param string $glue (default:'/')
     *
     * @return bool
     */
    protected function equals($string, $other, $strict=false, $glue='/')
    {
        return $this->newList($string, $glue)->equals($other, $strict);
    }
}
