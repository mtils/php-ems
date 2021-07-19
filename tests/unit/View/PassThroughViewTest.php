<?php
/**
 *  * Created by mtils on 19.07.2021 at 15:36.
 **/

namespace Ems\View;


use Ems\Core\Support\GenericRenderer;
use Ems\TestCase;

class PassThroughViewTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(\Ems\Contracts\View\View::class, $this->make());
    }

    /**
     * @test
     */
    public function toString_passes_content()
    {
        $view = $this->make('users.show', 'foo');
        $this->assertEquals('foo', (string)$view);
        $this->assertEquals('users.show', $view->name());
    }

    /**
     * @test
     */
    public function toString_uses_renderer_if_assigned()
    {
        $view = $this->make('users.show', 'foo');
        $renderer = new GenericRenderer(function (\Ems\Contracts\View\View $view) {
            return '-' . $view['content'] . '-' ;
        });
        $view->setRenderer($renderer);
        $this->assertEquals('-foo-', (string)$view);
    }

    protected function make($name='', $content='')
    {
        return new PassthroughView($name, $content);
    }
}