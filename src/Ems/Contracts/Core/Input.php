<?php
/**
 *  * Created by mtils on 20.08.18 at 10:33.
 **/

namespace Ems\Contracts\Core;



use Ems\Contracts\Routing\Routable;

interface Input extends Message, Provider, Routable
{

    /**
     * The custom attribute "pool". Only custom assgned attributes. Another
     * would be GET/POST/argv...
     */
    const POOL_CUSTOM = 'custom';

    /**
     * Return the *requested* locale.
     *
     * @return string
     */
    public function locale();

    /**
     * Return only input from $pool (GET,POST,PUT,ENV...)
     *
     * @param string $pool
     *
     * @return self
     */
    public function only($pool);

    /**
     * Return the (raw) content. In case of http it would be the body or a stream
     * of it.
     *
     * @return Content
     */
    public function content();

    /**
     * Return the previous input. This is for overwriting input like in
     * middlewares.
     *
     * @return self|null
     */
    public function previous();

    /**
     * Return the next input.
     *
     * @see self::previous()
     *
     * @return self|null
     */
    public function next();
}