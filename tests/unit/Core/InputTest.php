<?php
/**
 *  * Created by mtils on 22.06.19 at 08:32.
 **/

namespace Ems\Core;


use Ems\Contracts\Routing\GenericRouteScope;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteScope;
use Ems\Core\Filesystem\AsciiContent;
use Ems\TestCase;
use Ems\Contracts\Core\Input as InputContract;

class InputTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(InputContract::class, $this->newInput());
    }

    /**
     * @test
     */
    public function get_and_set_locale()
    {
        $input = $this->newInput();
        $this->assertSame($input, $input->setLocale('DE_de'));
        $this->assertEquals('DE_de', $input->locale());
    }

    /**
     * @test
     */
    public function get_and_set_content()
    {
        $text = '<html></html>';
        $input = $this->newInput();
        $content = AsciiContent::forString($text, 'text/html');
        $this->assertSame($input, $input->setContent($content));
        $this->assertEquals('text/html', $input->content()->mimeType());
        $this->assertEquals($text, (string)$input->content());
        $this->assertInstanceOf(AsciiContent::class, $input->content());
    }

    /**
     * @test
     */
    public function get_and_set_previous()
    {
        $previous = $this->newInput();
        $input = $this->newInput();
        $this->assertSame($input, $input->setPrevious($previous));
        $this->assertSame($previous, $input->previous());
        $this->assertSame($input, $previous->next());
    }

    /**
     * @test
     */
    public function get_and_set_previous_to_two_inputs()
    {
        $previous = $this->newInput();
        $input = $this->newInput();
        $this->assertSame($input, $input->setPrevious($previous));
        $this->assertSame($previous, $input->previous());
        $this->assertSame($input, $previous->next());

        $input2 = $this->newInput();

        $this->assertSame($input2, $input2->setPrevious($previous));
        $this->assertSame($previous, $input2->previous());
        $this->assertSame($input2, $previous->next());
    }

    /**
     * @test
     */
    public function get_and_set_previous_with_different_class()
    {
        $previous = $this->mock(InputContract::class);
        $input = $this->newInput();
        $this->assertSame($input, $input->setPrevious($previous));
        $this->assertSame($previous, $input->previous());
    }

    /**
     * @test
     */
    public function get_and_set_next()
    {
        $next = $this->newInput();
        $input = $this->newInput();
        $this->assertSame($input, $input->setNext($next));
        $this->assertSame($next, $input->next());
        $this->assertSame($input, $next->previous());
    }

    /**
     * @test
     */
    public function get_and_set_next_to_two_inputs()
    {
        $next = $this->newInput();
        $input = $this->newInput();
        $this->assertSame($input, $input->setNext($next));
        $this->assertSame($next, $input->next());
        $this->assertSame($input, $next->previous());

        $input2 = $this->newInput();

        $this->assertSame($input2, $input2->setNext($next));
        $this->assertSame($next, $input2->next());
        $this->assertSame($input2, $next->previous());
    }

    /**
     * @test
     */
    public function get_and_set_next_with_different_class()
    {
        $next = $this->mock(InputContract::class);
        $input = $this->newInput();
        $this->assertSame($input, $input->setNext($next));
        $this->assertSame($next, $input->next());
    }

    /**
     * @test
     */
    public function only_returns_copy()
    {
        $input = $this->newInput();
        $input['foo'] = 'bar';
        $customInput = $input->only(Input::POOL_CUSTOM);
        $this->assertEquals($input['foo'], $customInput['foo']);
        $this->assertNotSame($customInput, $input);
    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\UnsupportedParameterException
     */
    public function only_throws_exception_on_unknown_pool()
    {
        $this->newInput()->only('some-unknown-pool');
    }

    /**
     * @test
     */
    public function get_returns_known_value()
    {
        $input = $this->newInput();
        $input['foo'] = 'bar';
        $this->assertEquals('bar', $input->get('foo'));
        $this->assertEquals('bar', $input->getOrFail('foo'));
    }

    /**
     * @test
     */
    public function get_returns_default_value()
    {
        $input = $this->newInput();
        $input['foo'] = 'bar';
        $this->assertEquals('baz', $input->get('for', 'baz'));
    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\KeyNotFoundException
     */
    public function getOrFil_throws_exception_on_unknown_key()
    {
        $input = $this->newInput();
        $input['foo'] = 'bar';
        $input->getOrFail('for');
    }

    /**
     * @test
     */
    public function get_and_set_routeScope()
    {
        $input = $this->newInput();
        $this->assertNull($input->routeScope());
        $this->assertSame($input, $input->setRouteScope('admin'));
        $this->assertInstanceOf(RouteScope::class, $input->routeScope());
        $this->assertEquals('admin', (string)$input->routeScope());

        $newScope = new GenericRouteScope(42, 'master');
        $input->setRouteScope($newScope);
        $this->assertSame($newScope, $input->routeScope());
    }

    /**
     * @test
     */
    public function get_and_set_url()
    {
        $input = $this->newInput();
        $this->assertInstanceOf(\Ems\Contracts\Core\Url::class, $input->url());
        $this->assertEquals('', (string)$input->url());
        $url = new Url('https://web-utils.de');
        $this->assertSame($input, $input->setUrl($url));
        $this->assertSame($url, $input->url());
    }

    /**
     * @test
     */
    public function get_and_set_method()
    {
        $input = $this->newInput()->setMethod('GET');
        $this->assertEquals('GET', $input->method());
    }

    /**
     * @test
     */
    public function get_and_set_clientType()
    {
        $input = $this->newInput()->setClientType('api');
        $this->assertEquals('api', $input->clientType());
    }

    /**
     * @test
     */
    public function get_and_set_matchedRoute()
    {
        $input = $this->newInput();
        $this->assertNull($input->matchedRoute());
        $route = new Route('GET', 'foo', 'sleep');
        $this->assertSame($input, $input->setMatchedRoute($route));
        $this->assertSame($route, $input->matchedRoute());
    }

    /**
     * @test
     */
    public function get_and_set_routeParameters()
    {
        $input = $this->newInput();
        $this->assertSame([], $input->routeParameters());
        $parameters = ['foo' => 'bar'];
        $this->assertSame($input, $input->setRouteParameters($parameters));
        $this->assertEquals($parameters, $input->routeParameters());
    }

    /**
     * @test
     */
    public function get_and_set_handler()
    {
        $input = $this->newInput();
        $this->assertNull($input->getHandler());
        $handler = function () {};
        $this->assertSame($input, $input->setHandler($handler));
        $this->assertEquals($handler, $input->getHandler());
    }

    /**
     * @test
     */
    public function isRouted_returns_only_true_if_route_and_handler_assigned()
    {
        $input = $this->newInput();
        $this->assertFalse($input->isRouted());
        $handler = function () {};
        $this->assertSame($input, $input->setHandler($handler));
        $this->assertFalse($input->isRouted());
        $input->setMatchedRoute(new Route('GET', 'foo', 'FooHandler::bar'));
        $this->assertTrue($input->isRouted());
    }

    protected function newInput()
    {
        return new Input();
    }
}