<?php
/**
 *  * Created by mtils on 2/21/21 at 8:02 AM.
 **/

namespace Ems\Config\Processors;

use Ems\TestCase;

use function is_callable;

class ConfigVariablesParserTest extends TestCase
{
    /**
     * @test
     */
    public function it_instantiates()
    {
        $instance = $this->make();
        $this->assertInstanceOf(ConfigVariablesParser::class, $instance);
        $this->assertTrue(is_callable($instance));
    }

    /**
     * @test
     */
    public function replace_simple_variables()
    {
        $instance = $this->make();
        $env = [
            'PATH'      => '/usr/bin',
            'TIMEZONE'  => 'Europe/Berlin',
            'ENV'       => 'staging'
        ];
        $instance->assign('env', $env);

        $this->assertEquals('staging', $instance->parse('{env.ENV}'));
        $this->assertEquals('/usr/bin is the path, also in Europe/Berlin', $instance->parse('{env.PATH} is the path, also in {env.TIMEZONE}'));
        $this->assertEquals('The path to php is: /usr/bin/php', $instance->parse('The path to php is: {env.PATH}/php'));
        $this->assertEquals('bla', $instance->parse('bla'));
    }

    /**
     * @test
     */
    public function replace_string_without_placeholder_returns_same_string()
    {
        $instance = $this->make();
        $env = [
            'PATH'      => '/usr/bin',
            'TIMEZONE'  => 'Europe/Berlin',
            'ENV'       => 'staging'
        ];
        $instance->assign('env', $env);

        $this->assertEquals('bla', $instance->parse('bla'));
    }

    /**
     * @test
     */
    public function replace_string_with_not_existing_pool()
    {
        $instance = $this->make();
        $env = [
            'PATH'      => '/usr/bin',
            'TIMEZONE'  => 'Europe/Berlin',
            'ENV'       => 'staging'
        ];
        $this->assertSame([], $instance->getAssignments());
        $instance->assign('env', $env);
        $this->assertEquals(['env' => $env], $instance->getAssignments());

        $this->assertEquals('{foo.PATH}', $instance->parse('{foo.PATH}'));
    }

    /**
     * @test
     */
    public function replace_variables_from_multiple_sources()
    {
        $instance = $this->make();
        $env = [
            'PATH'      => '/usr/bin',
            'TIMEZONE'  => 'Europe/Berlin',
            'ENV'       => 'staging'
        ];
        $config = [
            'database' => [
                'connections' => [
                    'mysql' => [
                        'driver'    => 'mysql',
                        'host'      => 'localhost',
                        'database'  => 'ems_app',
                        'user'      => 'admin',
                        'password'  => 'password123'
                    ]
                ]
            ],
            'app' => [
                'url' => 'https://ems-application.com'
            ]
        ];
        $instance->assign('env', $env);
        $instance->assign('config', $config);

        $this->assertEquals($config['app']['url'], $instance->parse('{config.app.url}'));
        $this->assertEquals($config['database']['connections'], $instance->parse('{config.database.connections}'));

    }

    /**
     * @test
     */
    public function replace_variables_with_default_values()
    {
        $instance = $this->make();
        $env = [
            'PATH'      => '/usr/bin',
            'TIMEZONE'  => 'Europe/Berlin',
            'ENV'       => 'staging'
        ];
        $config = [
            'database' => [
                'connections' => [
                    'mysql' => [
                        'driver'    => 'mysql',
                        'host'      => 'localhost',
                        'database'  => 'ems_app',
                        'user'      => 'admin',
                        'password'  => 'password123'
                    ]
                ]
            ],
            'app' => [
                'url' => 'https://ems-application.com'
            ]
        ];
        $instance->assign('env', $env);
        $instance->assign('config', $config);

        $this->assertEquals('https://google.de', $instance->parse('{config.app.uri|https://google.de}'));
        $this->assertEquals($config['database']['connections'], $instance->parse('{config.database.connections}'));

    }

    /**
     * @test
     */
    public function invoke_replaces_all_variables()
    {
        $instance = $this->make();
        $env = [
            'PATH'      => '/usr/bin',
            'TIMEZONE'  => 'Europe/Berlin',
            'ENV'       => 'staging',
            'DATABASE_PASSWORD' => 'password_123'
        ];
        $config = [
            'database' => [
                'connection' => 'default',
                'connections' => [
                    'default' => [
                        'driver'    => 'mysql',
                        'host'      => 'localhost',
                        'database'  => 'ems_app',
                        'user'      => 'admin',
                        'password'  => '{env.DATABASE_PASSWORD}'
                    ]
                ]
            ],
            'app' => [
                'url' => 'https://ems-application.com',
                'name' => 'Application with a {config.database.connections.default.driver} database'
            ]
        ];
        $awaited = [
            'database' => [
                'connection' => 'default',
                'connections' => [
                    'default' => [
                        'driver'    => 'mysql',
                        'host'      => 'localhost',
                        'database'  => 'ems_app',
                        'user'      => 'admin',
                        'password'  => 'password_123'
                    ]
                ]
            ],
            'app' => [
                'url' => 'https://ems-application.com',
                'name' => 'Application with a mysql database'
            ]
        ];
        $instance->assign('env', $env);

        $this->assertEquals($awaited, $instance($config, $config));

    }

    protected function make() : ConfigVariablesParser
    {
        return new ConfigVariablesParser();
    }
}