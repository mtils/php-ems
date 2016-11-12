<?php

namespace Ems\Contracts\Core;

interface PathFinder extends AppPath
{
    /**
     * Return the AppPath for scope $scope.
     *
     * @param string
     *
     * @return \Ems\Contracts\Core\AppPath
     **/
    public function to($scope);

    /**
     * Mapp a AppPath object or simply a path to an url.
     *
     * @param string                             $scope
     * @param string|\Ems\Contracts\Core\AppPath $path
     * @param string                             $url   (optional)
     *
     * @return \Ems\Contracts\Core\AppPath
     **/
    public function map($scope, $path, $url = null);

    /**
     * Return all mapped scopes.
     *
     * @return array
     **/
    public function scopes();

    /**
     * Return a new instance of this PathFinder with a namespace prefix for all
     * scopes.
     *
     * @example PathFinder::namespaced('assets')->to('js')
     * is the same as:
     * @example PathFinder->to('assets::js')
     * The :: will always be added to the passed namespace
     *
     * @param string
     *
     * @return self
     **/
    public function namespaced($namespace);
}
