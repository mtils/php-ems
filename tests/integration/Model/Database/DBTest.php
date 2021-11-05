<?php
/**
 *  * Created by mtils on 05.11.2021 at 21:56.
 **/

namespace Ems\Model\Database;

use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Url;
use Ems\TestCase;

use function dirname;
use function var_dump;

class DBTest extends TestCase
{
    /**
     * @test
     */
    public function configToUrl_creates_a_sqlite_connection()
    {
        $config = [
            'driver' => 'sqlite',
            'database' => 'local/storage/app.db'
        ];
        $url = DB::configToUrl($config, dirname($this->dirOfTests()));
        $this->assertInstanceOf(UrlContract::class, $url);
        $absolutePath = dirname($this->dirOfTests()) . '/'. $config['database'];
        $this->assertEquals($config['driver'] . "://$absolutePath", (string)$url);
    }

    /**
     * @test
     */
    public function configToUrl_creates_a_sqlite_connection_with_absolute_database_path()
    {
        $config = [
            'driver' => 'sqlite',
            'database' => '/local/storage/app.db'
        ];
        $url = DB::configToUrl($config, dirname($this->dirOfTests()));
        $this->assertInstanceOf(UrlContract::class, $url);
        $absolutePath = $config['database'];
        $this->assertEquals($config['driver'] . "://$absolutePath", (string)$url);
    }

    /**
     * @test
     */
    public function configToUrl_creates_a_mysql_connection()
    {
        $config = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'ems_app',
            'user' => 'michael',
            'password' => 'secret'
        ];
        $url = DB::configToUrl($config, dirname($this->dirOfTests()));
        $this->assertInstanceOf(UrlContract::class, $url);
        $this->assertEquals($config['driver'] . "://{$config['user']}:xxxxxx@{$config['host']}/{$config['database']}", (string)$url);
        $this->assertEquals($config['password'], $url->password);
    }

    /**
     * @test
     */
    public function configurationToUrls_creates_connection_urls()
    {
        $sqliteConfig = [
            'driver' => 'sqlite',
            'database' => '/local/storage/app.db'
        ];

        $mysqlConfig = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'ems_app',
            'user' => 'michael',
            'password' => 'secret'
        ];
        $configurations = [
            'default' => $sqliteConfig,
            'archive' => $mysqlConfig
        ];
        $urls = DB::configurationsToUrls($configurations);
        $this->assertInstanceOf(UrlContract::class, $urls['default']);
        $this->assertInstanceOf(UrlContract::class, $urls['archive']);
        $this->assertEquals($mysqlConfig['driver'] . "://{$mysqlConfig['user']}:xxxxxx@{$mysqlConfig['host']}/{$mysqlConfig['database']}", (string)$urls['archive']);
        $this->assertEquals($mysqlConfig['password'], $urls['archive']->password);
        $absolutePath = $sqliteConfig['database'];
        $this->assertEquals($sqliteConfig['driver'] . "://$absolutePath", (string)$urls['default']);
    }

    /**
     * @test
     */
    public function makeConnectionHandler_creates_correct_connection()
    {
        $sqliteConfig = [
            'driver' => 'sqlite',
            'database' => '/local/storage/app.db'
        ];

        $mysqlConfig = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'ems_app',
            'user' => 'michael',
            'password' => 'secret'
        ];
        $configurations = [
            'default' => $sqliteConfig,
            'archive' => $mysqlConfig
        ];
        $urls = DB::configurationsToUrls($configurations);
        $connectionFactory = DB::makeConnectionHandler($urls);
        $connection = $connectionFactory('default');
        $this->assertInstanceOf(PDOConnection::class, $connection);
        $absolutePath = $sqliteConfig['database'];
        $this->assertEquals($sqliteConfig['driver'] . "://$absolutePath", (string)$connection->url());

        $this->assertNull($connectionFactory('foo'));
    }

    /**
     * @test
     */
    public function urlToConfig_creates_a_mysql_config()
    {
        $config = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'ems_app',
            'user' => 'michael',
            'password' => 'secret'
        ];

        $urlString = $config['driver'] . "://{$config['user']}:{$config['password']}@{$config['host']}/{$config['database']}";
        $url = new Url($urlString);
        $this->assertEquals($config, Db::urlToConfig($url));
    }

    /**
     * @test
     */
    public function urlToConfig_creates_a_sqlite_config()
    {
        $config = [
            'driver' => 'sqlite',
            'database' => '/local/storage/app.db'
        ];

        $absolutePath = $config['database'];
        $urlString = $config['driver'] . "://$absolutePath";

        $url = new Url($urlString);
        $this->assertEquals($config, Db::urlToConfig($url));
    }

    /**
     * @test
     */
    public function urlToConfig_creates_a_mysql_config_with_port()
    {
        $config = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'ems_app',
            'user' => 'michael',
            'password' => 'secret',
            'port' => 455
        ];

        $urlString = $config['driver'] . "://{$config['user']}:{$config['password']}@{$config['host']}:{$config['port']}/{$config['database']}";
        $url = new Url($urlString);
        $this->assertEquals($config, Db::urlToConfig($url));
    }
    /**
     * @test
     */
    public function urlToConfig_creates_a_mysql_config_with_parameters()
    {
        $config = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'ems_app',
            'user' => 'michael',
            'password' => 'secret',
            'port' => 455,
            'persistent' => 1,
            'charset' => 'utf-8'
        ];

        $urlString = $config['driver'] . "://{$config['user']}:{$config['password']}@{$config['host']}:{$config['port']}/{$config['database']}";
        $url = new Url($urlString);
        $url = $url->query(['persistent' => $config['persistent'], 'charset' => $config['charset']]);
        $this->assertEquals($config, Db::urlToConfig($url));
    }
}