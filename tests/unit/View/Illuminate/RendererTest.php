<?php

namespace Ems\View\Illuminate;

use Ems\Contracts\Core\Renderer as RendererContract;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Ems\View\Highlight;

class RendererTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(RendererContract::class, $this->newRenderer());
    }

    public function test_canRender_returns_true_on_view()
    {
        $this->assertTrue($this->newRenderer()->canRender(new Highlight));
    }

    public function test_render_calls_illuminate_view_with_parameters()
    {
        $view = (new Highlight())->render('foo.bar')->assign('eleven', 'monkeys');

        $factory = $this->mockFactory();

        $illuminateVIew = $this->mock(View::class);
        $illuminateVIew->shouldReceive('render')->andReturn('lalala');

        $factory->shouldReceive('make')
                ->with('foo.bar', ['eleven'=>'monkeys'])
                ->andReturn($illuminateVIew);

        $renderer = $this->newRenderer($factory);

        $this->assertEquals('lalala', $renderer->render($view));
    }

    protected function newRenderer(Factory $factory=null)
    {
        return new Renderer($factory ?: $this->mockFactory());
    }

    protected function mockFactory()
    {
        return $this->mock(Factory::class);
    }
}
