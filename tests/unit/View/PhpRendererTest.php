<?php
/**
 *  * Created by mtils on 29.11.2021 at 21:05.
 **/

namespace Ems\View;

use Ems\TestCase;
use Ems\TestData;
use Ems\Contracts\Core\Renderer;

use function json_encode;

class PhpRendererTest extends TestCase
{
    use TestData;

    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(Renderer::class, $this->make());
    }

    /**
     * @test
     */
    public function it_renders_view()
    {
        $renderer = $this->make();
        $view = new View('users.index');
        $users = [
            'tils@ipo.de',
            'michael@tils.de',
            'foo@bar.de'
        ];
        $view->assign(['users' => $users]);

        $this->assertEquals(json_encode($users), $renderer->render($view));
    }

    /**
     * @return PhpRenderer
     */
    protected function make(ViewFileFinder $finder=null)
    {
        return new PhpRenderer($finder ?: $this->finder());
    }

    protected function finder()
    {
        return (new ViewFileFinder())->addPath($this->dirOfTests('views'));
    }
}