<?php

namespace Ems\Assets\Parser;

use JShrink\Minifier;

class JShrinkParserTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Core\TextParser',
            $this->newParser()
        );
    }

    public function test_parse_compresses_js()
    {
        $fatJs = 'var foo="bold";
                  window.open("_blank");

                  //comment';

        $minified = $this->newParser()->parse($fatJs, []);

        $this->assertLessThan(strlen($fatJs), strlen($minified));
        $this->assertStringContainsString('_blank', $minified);
        $this->assertStringContainsString('bold', $minified);
    }

    protected function newParser(Minifier $minifier=null)
    {
        return new JShrinkParser($minifier);
    }
}

