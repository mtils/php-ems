<?php

namespace Ems\Core;

use Ems\Contracts\Core\AppPath as AppPathContract;
use InvalidArgumentException;
use Exception;

class AppPath implements AppPathContract
{
    /**
     * @var string
     **/
    protected $basePath = '';

    /**
     * @var string
     */
    protected $baseUrl = '';

    /**
     * @var callable
     **/
    protected $baseUrlProvider;

    /**
     * @var bool
     **/
    protected $checkFilesystem = false;

    /**
     * Return the path offset. (/srv/www/htdocs/wordpress).
     * 
     * @return string
     **/
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Set the absolute path. (/srv/www/htdocs/wordpress).
     *
     * @param string
     *
     * @return self
     **/
    public function setBasePath($path)
    {
        $this->basePath = rtrim($path, '/');

        return $this;
    }

    /**
     * Return the base url (http://your-domain.com/uploads).
     *
     * @return string
     **/
    public function getBaseUrl()
    {
        if ($this->baseUrl) {
            return $this->baseUrl;
        }
        if ($this->baseUrlProvider) {
            return call_user_func($this->baseUrlProvider, $this);
        }

        return $this->baseUrl;
    }

    /**
     * Set the base url (http://your-domain.com/uploads).
     *
     * @param string
     *
     * @return self
     **/
    public function setBaseUrl($url)
    {
        $this->baseUrl = $url == '/' ? $url : rtrim($url, '/');

        if (!$this->baseUrl) {
            throw new InvalidArgumentException('The baseUrl cannot be empty');
        }
//         \Log::info("url: '$url' baseUrl: '$url' basePath: {$this->basePath}");
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string
     *
     * @return string
     **/
    public function relative($url)
    {
        if (!$url || $url == '/') {
            return '.';
        }

        if (strpos($url, '..') !== false) {
            throw new InvalidArgumentException('Double dots in paths are not allowed');
        }

        $baseUrl = $this->getBaseUrl();

        if ($baseUrl && strpos($url, $baseUrl) === 0) {
            return trim(str_replace($baseUrl, '', $url), '/');
        }

        if ($this->startsWithBasePath($url) || !$this->checkFilesystem) {
            return trim(str_replace($this->basePath, '', $url), '/');
        }

        return static::getRelativePath($this->basePath, $url);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path (optional)
     *
     * @return string
     **/
    public function absolute($relativePath = null)
    {
        if ($this->isEmptyPath($relativePath)) {
            return $this->basePath;
        }

        if ($this->startsWithBasePath($relativePath)) {
            return $relativePath;
        }

        if (!$baseUrl = $this->getBaseUrl()) {
            return $this->basePath.'/'.trim($relativePath, '/');
        }

        if ($this->startsWithBaseUrl($relativePath)) {
            return $this->absolute($this->relative($relativePath));
        }

        return $this->basePath.'/'.trim($relativePath, '/');
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path (optional)
     *
     * @return string
     **/
    public function url($path = null)
    {
        if ($this->isEmptyPath($path)) {
            return $this->getBaseUrl();
        }
        if ($this->containsScheme($path)) {
            return $path;
        }

        $baseUrl = $this->getBaseUrl();

        return $this->getBaseUrl().($baseUrl == '/' ? '' : '/').trim($path, '/');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function __toString()
    {
        try {
            return $this->absolute();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * On multi domain applications it is better to assign a callable which
     * provides the baseUrl.
     * A manually setted baseUrl (via setBaseUrl) will be preferred. So you
     * can manually set the url in cli environments.
     *
     * @param callable $baseUrlProvider
     *
     * @return self
     **/
    public function provideBaseUrl(callable $baseUrlProvider)
    {
        $this->baseUrlProvider = $baseUrlProvider;

        return $this;
    }

    public function enableFilesystemChecks($enabled = true)
    {
        $this->checkFilesystem = $enabled;

        return $this;
    }

    public static function getRelativePath($from, $to)
    {

        // some compatibility fixes for Windows paths
        $from = is_dir($from) ? rtrim($from, '\/').'/' : $from;
        $to = is_dir($to)   ? rtrim($to, '\/').'/'   : $to;
        $from = str_replace('\\', '/', $from);
        $to = str_replace('\\', '/', $to);

        $from = explode('/', $from);
        $to = explode('/', $to);
        $relPath = $to;

        foreach ($from as $depth => $dir) {
            // find first non-matching dir
            if ($dir === $to[$depth]) {
                // ignore this directory
                array_shift($relPath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if ($remaining > 1) {
                    // add traversals up to first matching dir
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                } else {
                    $relPath[0] = './'.$relPath[0];
                }
            }
        }

        return implode('/', $relPath);
    }

    /**
     * Check if a path contains a scheme (and dont have to be mapped).
     *
     * @param string $path
     *
     * @return bool
     **/
    protected function containsScheme($path)
    {
        // If the path starts with // it is http:// or https://
        if (strpos($path, '//') === 0) {
            return true;
        }

        // If no :// is in $path it surely does not contain a scheme
        if (strpos($path, '://') === false) {
            return false;
        }

        $parsed = parse_url($path);

        return isset($parsed['scheme']) && $parsed['scheme'];
    }

    /**
     * @param string $path
     *
     * @return bool
     **/
    protected function startsWithBasePath($path)
    {
        return strpos($path, $this->basePath) === 0;
    }

    /**
     * @param string $path
     *
     * @return bool
     **/
    protected function startsWithBaseUrl($path)
    {
        return strpos($path, $this->getBaseUrl()) === 0;
    }

    /**
     * Return if the path can be considered as empty.
     *
     * @param string $path
     *
     * @return bool
     **/
    protected function isEmptyPath($path)
    {
        return $path == '/' || $path == '.' || $path == '' || $path === null;
    }
}
