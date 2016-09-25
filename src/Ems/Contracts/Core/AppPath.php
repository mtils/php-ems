<?php

namespace Ems\Contracts\Core;

/**
 * An AppPath represents a path in your application.
 * Use it as a string and you get the absolute path.
 * Retrieve urls, relative paths by its methods.
 **/
interface AppPath
{

    /**
     * Return the relative path of an absolute path
     * (/srv/www/htdocs/wordpress/wp-content/favicon.ico => wp-content/favicon.ico)
     *
     * @param string $absolutePath
     * @return string
     **/
    public function relative($absolutePath);

    /**
     * Return the absolute path of a relative path
     * (wp-content/favicon.ico => /srv/www/htdocs/wordpress/wp-content/favicon.ico)
     *
     * @param string $path (optional)
     * @return string
     **/
    public function absolute($relativePath=null);

    /**
     * Calculate the url of the passed relative path
     * (header/logo.gif => http://your-domain.com/uploads/header/logo.gif)
     *
     * @param string $path (optional)
     * @return string
     **/
    public function url($path=null);

    /**
     * The string representation of this mapper. This
     * is the root url / same as self::absolute()
     * This is handy for PathFinder. So a call like
     * PathFinder::path('app') makes sense because it returns a AppPath
     *
     * @return string
     **/
    public function __toString();

}
