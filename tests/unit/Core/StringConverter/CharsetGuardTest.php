<?php

namespace Ems\Core\StringConverter;

use Ems\Testing\Cheat;
use Ems\Core\Exceptions\InvalidCharsetException;

class CharsetGuardTest extends \Ems\TestCase
{

    public function test_is_instanciable()
    {
        $this->assertInstanceOf(CharsetGuard::class, $this->newGuard());
    }

    public function test_isAscii_returns_right_values()
    {
        $this->assertTrue($this->newGuard()->isAscii('Haha hoho how are you?'));
        $this->assertFalse($this->newGuard()->isAscii('Haha hoho how are you blä?'));
        $this->assertTrue($this->newGuard()->isAscii('!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~'));
        $this->assertFalse($this->newGuard()->isAscii('nö'));
    }

    public function test_isUtf8_returns_right_values()
    {
        $this->assertFalse($this->newGuard()->isUtf8('Haha hoho how are you?'));
        $this->assertTrue($this->newGuard()->isUtf8('Haha hoho how are you blä?'));
        $this->assertFalse($this->newGuard()->isUtf8('!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~'));
        $this->assertTrue($this->newGuard()->isUtf8('nö'));
        $this->assertFalse($this->newGuard()->isUtf8($this->e('nö', 'iso-8859-1')));
    }

    public function test_detect_detects_utf8()
    {
        $this->assertCharsetIs('utf-8', 'Hähä wär däß nüx?');
    }

    public function test_detect_detects_ascii_if_no_special_chars_in_it()
    {
        $this->assertCharsetIs('ascii', 'How are you?');
    }

    public function test_detect_iso()
    {
        $this->assertCharsetIs('iso-8859-1', 'Bübeli auf dem Schoß', 'iso-8859-1');
    }

    /**
     * This should work but dont...
     */
    public function _test_detect_cp1552()
    {
        $this->assertCharsetIs('Windows-1252', 'Bübeli auf dem Schoß für ~3 €', 'Windows-1252');
    }

    public function test_detect_by_bom_skips_rest()
    {
        $bom = $this->newGuard()->bom(CharsetGuard::UTF8);

        $test = $bom . 'Only ascii letters';

        $this->assertCharsetIs('utf-8', $bom);

    }

    public function test_findCharsetByBOM_returns_empty_string_if_charset_not_found()
    {
        $guard = Cheat::a($this->newGuard());
        $this->assertSame('', $guard->findCharsetByBOM('foo'));
    }

    public function test_withoutBOM_removes_bom_or_not_if_not_in_string()
    {

        $guard = $this->newGuard();
        $bom = $guard->bom(CharsetGuard::UTF8);

        $withoutBom = 'Only ascii letters';
        $withBom = $bom . $withoutBom;

        $this->assertSame($withoutBom, $guard->withoutBOM($withBom));
        $this->assertSame($withoutBom, $guard->withoutBOM($withoutBom));
    }

    public function test_isCharset_returns_true_if_matches()
    {

        $guard = $this->newGuard();
        $test = 'Hällö';
        $iso = $this->e($test, 'iso-8859-1');
        $this->assertTrue($guard->isCharset($iso, 'iso-8859-1'));
        $this->assertFalse($guard->isCharset($test, 'iso-8859-1'));
        $this->assertTrue($guard->isCharset($test, 'utf-8'));

    }

    public function test_forceCharset_throws_no_exception_if_matches()
    {

        $guard = $this->newGuard();
        $test = 'Hällö';
        $iso = $this->e($test, 'iso-8859-1');
        $this->assertNull($guard->forceCharset($iso, 'iso-8859-1'));

    }

    public function test_forceCharset_throws_exception_if_not_matches()
    {

        $guard = $this->newGuard();
        $test = 'Hällö';
        $iso = $this->e($test, 'iso-8859-1');

        try {

            $guard->forceCharset($iso, 'utf-8');
            $this->fail('forceCharset should throw an exception');

        } catch (InvalidCharsetException $e) {
            $this->assertEquals($e->awaitedCharset(), 'utf-8');
            $this->assertEquals($e->failedString(), $iso);
            $this->assertEquals(strtolower($e->suggestedCharset()), 'iso-8859-1');
            $this->assertStringContainsString('iso-8859-1', strtolower($e->getHelp()));
        }

    }

    public function test_forceCharset_throws_exception_if_not_matches_iso()
    {

        $guard = $this->newGuard();
        $test = 'Hällö';
        $iso = $this->e($test, 'iso-8859-1');

        try {

            $guard->forceCharset($test, 'iso-8859-1');
            $this->fail('forceCharset should throw an exception');

        } catch (InvalidCharsetException $e) {
            $this->assertEquals($e->awaitedCharset(), 'iso-8859-1');
            $this->assertEquals($e->failedString(), $test);
            $this->assertEquals(strtolower($e->suggestedCharset()), 'utf-8');
            $this->assertStringContainsString('utf-8', strtolower($e->getHelp()));
        }

    }

    public function test_InvalidCharsetException_getHelp_returns_generic_message_if_charset_not_detectable()
    {

        $guard = $this->newGuard();

        $guard = $this->mock(CharsetGuard::class);

        $test = 'Д';
        $awaited = 'utf-8';
        $detected = '';

        $guard->shouldReceive('detect')
              ->with($test)
              ->atLeast()->once()
              ->andReturn('');

        $e = (new InvalidCharsetException($test, $awaited))->useGuard($guard);

        $this->assertStringContainsString('undetectable', $e->getHelp());
        $this->assertStringContainsString('utf-8', $e->getHelp());

    }

    public function test_InvalidCharsetException_creates_guard_by_itself()
    {

        $test = 'Hallö';
        $awaited = 'iso-8859-1';

        $e = new InvalidCharsetException($test, $awaited);

        $this->assertEquals('utf-8', strtolower($e->suggestedCharset()));

    }

    protected function assertCharsetIs($awaited, $string, $encodeTo='utf-8', $strict=false)
    {
        $guard = $this->newGuard();
        $string = ($encodeTo == 'utf-8') ? $string : $this->e($string, $encodeTo);
        $detected = $guard->detect($string, [], $strict);

        return $this->assertEquals(strtolower($awaited), strtolower($detected), "Failed to detect encoding as $awaited");
    }

    protected function e($string, $encoding)
    {
        return mb_convert_encoding($string, $encoding);
    }

    protected function newGuard()
    {
        return new CharsetGuard;
    }


}
