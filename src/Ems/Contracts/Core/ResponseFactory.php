<?php
/**
 *  * Created by mtils on 28.11.2021 at 17:33.
 **/

namespace Ems\Contracts\Core;

interface ResponseFactory
{

    /**
     * @param string|Stringable $content
     * @return Response
     */
    public function create($content) : Response;

    /**
     * Create the view $name with $data and output it.
     *
     * @param string $name
     * @param array $data (optional)
     * @return Response
     */
    public function view(string $name, array $data=[]) : Response;

    /**
     * Return a direct to $to. Pass an url for manual urls, a string for route
     * names
     *
     * @param string|Url $to
     * @param array      $routeParams
     * @return Response
     */
    public function redirect($to, array $routeParams=[]) : Response;

}