<?php
/**
 *  * Created by mtils on 02.01.18 at 09:04.
 **/

namespace Ems\Model;


use Ems\Contracts\Core\Checker;
use Ems\Contracts\Core\Extractor;
use Ems\Expression\Matcher;
use Ems\IntegrationTest;
use Ems\Testing\Cheat;

class SearchIntegrationTest extends IntegrationTest
{
    public function test_checker_is_singleton()
    {
        $checker1 = $this->app(Checker::class);
        $checker2 = $this->app(Checker::class);

        $this->assertInstanceOf(Checker::class, $checker1);
        $this->assertSame($checker1, $checker2);
    }

    public function test_matcher_is_singleton()
    {
        $matcher1 = $this->app(Matcher::class);
        $matcher2 = $this->app(Matcher::class);

        $this->assertInstanceOf(Matcher::class, $matcher1);
        $this->assertSame($matcher1, $matcher2);
    }

    public function test_resolve_PhpSearchEngine()
    {
        $matcher = $this->app(Matcher::class);
        $extractor = $this->app(Extractor::class);

        $engine = $this->app(PhpSearchEngine::class);
        $this->assertInstanceOf(PhpSearchEngine::class, $engine);

        // Test if the singletons were injected
        $this->assertSame($matcher, Cheat::get($engine,'matcher'));
        $this->assertSame($extractor, Cheat::get($engine,'extractor'));

    }
}