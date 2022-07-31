<?php

namespace Ems\Contracts\Core;

use Ems\TestCase;

/**
 *  * Created by mtils on 30.07.2022 at 08:17.
 **/

class StrTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(Stringable::class, $this->str());
    }

    /**
     * @test
     */
    public function like_matches_strings()
    {
        $this->assertTrue($this->str('Hello')->isLike('Hello'));
        $this->assertTrue($this->str('Hello')->isLike('hello'));
        $this->assertTrue($this->str('Hello')->isLike('h_llo'));
        $this->assertTrue($this->str('Hello')->isLike('he%'));
        $this->assertFalse($this->str('Hello')->isLike('ell%'));
        $this->assertTrue($this->str('Hello foo my name is bar')->isLike('%my na_e is%'));
        $this->assertFalse($this->str('Hello foo my name is bar')->isLike('my na_e is%'));
        $this->assertFalse($this->str('Hello foo my name is bar')->isLike('%my na_e is'));
    }

    protected function str(string $str='') : Str
    {
        return new Str($str);
    }
}