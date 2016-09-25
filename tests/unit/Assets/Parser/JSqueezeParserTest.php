<?php

namespace Ems\Assets\Parser;

use Patchwork\JSqueeze;

class JSqueezeParserTest extends \Ems\TestCase
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
        $this->assertContains('_blank', $minified);
        $this->assertContains('bold', $minified);
    }

    protected function newParser(JSqueeze $minifier=null)
    {
        return new JSqueezeParser($minifier);
    }

}

