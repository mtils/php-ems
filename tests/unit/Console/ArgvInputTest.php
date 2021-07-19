<?php
/**
 *  * Created by mtils on 18.07.2021 at 10:56.
 **/

namespace Ems\Console;


use Ems\Contracts\Routing\Command;
use Ems\Contracts\Routing\Routable;
use Ems\Contracts\Routing\Route;
use Ems\TestCase;

class ArgvInputTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(ArgvInput::class, $this->make());
    }

    /**
     * @test
     */
    public function it_assigns_arguments_as_query_parameters_on_get()
    {
        $tenant = '101';
        $input = $this->make(['console', $tenant]);

        $command = (new Command('users:index'))->argument('tenant');
        $input->setMatchedRoute((new Route('GET', 'users', ''))
                                    ->command($command));

        $this->assertEquals($tenant, $input->get('tenant'));

    }

    /**
     * @test
     */
    public function it_assigns_arguments_as_query_parameters_on_getOfFail()
    {
        $tenant = '101';
        $input = $this->make(['console', $tenant]);

        $command = (new Command('users:index'))->argument('tenant');
        $input->setMatchedRoute((new Route('GET', 'users', ''))
                                    ->command($command));

        $this->assertEquals($tenant, $input->getOrFail('tenant'));

    }

    /**
     * @test
     */
    public function it_assigns_arguments_as_query_parameters_on_offsetExists()
    {
        $tenant = '101';
        $input = $this->make(['console', $tenant]);

        $command = (new Command('users:index'))->argument('tenant');
        $input->setMatchedRoute((new Route('GET', 'users', ''))
                                    ->command($command));

        $this->assertTrue($input->offsetExists('tenant'));
        $this->assertFalse($input->offsetExists('foo'));

    }

    /**
     * @test
     */
    public function it_assigns_arguments_as_query_parameters_on_offsetGet()
    {
        $tenant = '101';
        $input = $this->make(['console', $tenant]);

        $command = (new Command('users:index'))->argument('tenant');
        $input->setMatchedRoute((new Route('GET', 'users', ''))
                                    ->command($command));

        $this->assertEquals($tenant, $input['tenant']);

    }

    /**
     * @test
     */
    public function it_assigns_arguments_as_query_parameters_on_toArray()
    {
        $tenant = '101';
        $input = $this->make(['console', $tenant]);

        $command = (new Command('users:index'))->argument('tenant');
        $input->setMatchedRoute((new Route('GET', 'users', ''))
                                    ->command($command));

        $inputData = $input->toArray();
        $this->assertEquals($tenant, $inputData['tenant']);
    }

    /**
     * @test
     */
    public function it_assigns_options_as_query_parameters_on_get()
    {
        $tenant = '101';
        $force = '';
        $input = $this->make(['console', $tenant, '--force']);

        $command = (new Command('users:index'))->argument('tenant')
        ->option('force');
        $input->setMatchedRoute((new Route('GET', 'users', ''))
                                    ->command($command));

        $this->assertTrue($input->get('force'));
        $this->assertEquals($tenant, $input->get('tenant'));

    }

    /**
     * @param array $argv
     * @return ArgvInput
     */
    public function make(array $argv=[])
    {
        $input = new ArgvInput($argv);
        return $input->setClientType(Routable::CONSOLE);
    }
}