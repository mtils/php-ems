<?php

namespace Ems\Contracts\Assets;

interface Asset extends Renderable
{
    /**
     * Return the name of this asset, the $asset param of
     * its registrar.
     *
     * @return string
     **/
    public function name();

    /**
     * Return if this asset is compiled.
     *
     * @return bool
     **/
    public function isCompiled();

    /**
     * (If this asset is compiled) return a list
     * of assets which are contained in this asset.
     *
     * @return \Ems\Contracts\Assets\Collection
     **/
    public function collection();

    /**
     * Return the uri (url for browser or resulting string).
     *
     * @return string
     **/
    public function uri();

    /**
     * Return absolute path to the file.
     *
     * @return string
     **/
    public function path();

    /**
     * Return if this is an inline asset.
     *
     * @return bool
     **/
    public function isInline();

    /**
     * Return the content (if inline).
     *
     * @return string
     **/
    public function content();

    /**
     * Return if this is a binary asset.
     *
     * @return bool
     **/
    public function isBinary();

    /**
     * Additional html attributes ($key=>$value).
     *
     * @return array
     **/
    public function attributes();
}
