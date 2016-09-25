<?php

namespace Ems\Assets\Parser;

class CssMinParserTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Core\TextParser',
            $this->newParser()
        );
    }

    public function test_parse_compresses_css()
    {
        $fatCss = 'table.classic {
                    font-size: bold;
                    border: 1px solid black;
                    /** border-collapse: collapse **/
                  }';

        $minified = $this->newParser()->parse($fatCss, []);

        $this->assertLessThan(strlen($fatCss), strlen($minified));
        $this->assertContains('table.classic', $minified);
        $this->assertContains('1px', $minified);
    }

    protected function newParser(JSqueeze $minifier=null)
    {
        return new CssMinParser($minifier);
    }

}

