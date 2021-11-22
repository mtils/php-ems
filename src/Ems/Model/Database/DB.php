<?php
/**
 *  * Created by mtils on 05.11.2021 at 11:29.
 **/

namespace Ems\Model\Database;

use Ems\Contracts\Core\Url;
use Ems\Core\Application;
use Ems\Core\Url as UrlObject;

use function basename;
use function class_exists;
use function defined;
use function dirname;

/**
 * This is a tool for some database related tasks. All SQL specific stuff is in
 * class SQL.
 */
class DB
{
    public static function urlToConfig(Url $url) : array
    {
        if($url->scheme == 'sqlite') {
            return [
                'driver'    => 'sqlite',
                'database'  => $url->host == 'memory' ? ':memory:' : (string)$url->path
            ];
        }
        $config = [
            'driver'    => $url->scheme,
            'host'      => $url->host,
            'database'  => $url->path->first(),
            'user'      => $url->user,
            'password'  => $url->password
        ];
        if ($url->port) {
            $config['port'] = $url->port;
        }
        foreach ($url->query as $key=>$value) {
            $config[$key] = $value;
        }

        return $config;
    }

    public static function configToUrl(array $config, string $databasePath='') : Url
    {
        if($config['driver'] == 'sqlite') {
            if ($config['database'][0] == '/') {
                return new UrlObject("sqlite:///{$config['database']}");
            }
            if ($config['database'] == ':memory:') {
                return new UrlObject("sqlite://memory");
            }
            $databasePath = $databasePath ?: self::guessAppPath();
            $path = $databasePath . '/' . ltrim($config['database'], '/');
            return new UrlObject("sqlite:///$path");
        }

        return (new UrlObject())
            ->scheme($config['driver'])
            ->host($config['host'])
            ->path($config['database'])
            ->user($config['user'])
            ->password($config['password']);
    }

    /**
     * Create urls from the passed configurations and index them by connection names.
     *
     * @param array $configurations
     * @param string $databasePath
     *
     * @return Url[]
     */
    public static function configurationsToUrls(array $configurations, string $databasePath='') : array
    {
        $urls = [];
        foreach ($configurations as $name=>$config) {
            $urls[$name] = self::configToUrl($config, $databasePath);
        }
        return $urls;
    }

    /**
     * Make a connection handler for connection pool to create the connection.
     *
     * @param array $nameToUrl
     * @param array $config (optional)
     * @return callable
     */
    public static function makeConnectionHandler(array $nameToUrl, array $config=[]) : callable
    {

        return function ($name) use ($nameToUrl, $config) {

            if ($name instanceof Url && $name->scheme == 'database') {
                $name = $name->host;
            }

            if ($name == 'default' && isset($config['connection'])) {
                $name = $config['connection'];
            }

            $nameString = (string)$name;
            if (isset($nameToUrl[$nameString])) {
                return new PDOConnection($nameToUrl[$nameString]);
            }
            return null;
        };

    }

    /**
     * @return string
     */
    private static function guessAppPath() : string
    {
        // Skeleton
        if (defined('APP_ROOT')) {
            return APP_ROOT;
        }
        // Laravel
        if (isset($_ENV['APP_BASE_PATH']) && $_ENV['APP_BASE_PATH']) {
            return $_ENV['APP_BASE_PATH'];
        }
        // was booted
        if (class_exists(Application::class, false) && Application::current()) {
            return (string)Application::current()->path();
        }
        // Guess by php
        $calledScript = $_SERVER['SCRIPT_FILENAME'];
        // Assume it is an index.php in a "public" directory
        if (basename($calledScript) == 'index.php') {
            return dirname($calledScript, 2);
        }
        return dirname($calledScript);

    }
}