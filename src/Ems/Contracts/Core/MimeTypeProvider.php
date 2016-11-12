<?php

namespace Ems\Contracts\Core;

interface MimeTypeProvider
{
    /**
     * Return the mimetype of $fileName. Only check per its name
     * (extension). It should work if you only pass an extension
     * (without dots) and a complete path and just a filename
     * Users of this method can be sure that no expensive operations
     * are triggered so dont check actual files here.
     *
     * @param string $fileName
     *
     * @return string
     **/
    public function typeOfName($fileName);

    /**
     * Verbosly check the mimetype of a file. (call finfo, file $path,...)
     * and return the detected mimetype.
     *
     * @param $path
     *
     * @return string
     **/
    public function typeOfFile($path);

    /**
     * Check if the passed $fileName has mimetype $type
     * Here is the place to register aliases, check case insensitive
     * , handle the plus sign stuff and so on to allow readable
     * code for users of this class.
     *
     * @param string $fileName
     * @param string $type     The awaited type
     * @param bool   $verbose  (optional) Check with typeOfFile
     *
     * @return bool
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     **/
    public function isOfType($fileName, $type, $verbose = false);
}
