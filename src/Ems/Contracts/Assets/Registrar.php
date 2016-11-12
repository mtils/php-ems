<?php

namespace Ems\Contracts\Assets;

/**
 * The asset registrar is the place to go to import you assets.
 **/
interface Registrar
{
    /**
     * Import a file/lib/asset named $asset. Add it to the group named by its
     * file extension. If you want to add it to a different group, pass
     * it manually by $group.
     *
     * you have three options for the $asset parameter: string, indexed array and
     * assoc array
     *
     * A string will be treated as a simple assert name
     * A indexed array will be considered as a list of asserts (many)
     * An associative array can have a name and other keys
     * The name will be the asset name, all other keys will be asset.attributes
     *
     * @example Registrar::import('jquery.js') // Adds the file to the js group
     * @example Registrar::import('jquery.js','base-js') // adds it ro base-js group
     * @example Registrar::import(['jquery.js','jquery-ui.js'],'base') // adds both files to base group
     * @example Registrar::import(['name' => 'print.css', 'media'=>'print'],'base') // adds print.css with media="print"
     *
     * @param string|array $asset
     * @param string       $group (optional)
     *
     * @return self
     **/
    public function import($asset, $group = null);

    /**
     * Shows inline content.
     *
     * @param string $asset
     * @param string $content
     * @param string $group   (optional)
     *
     * @return self
     **/
    public function inline($asset, $content, $group = null);

    /**
     * Create a new assert object. Automatically assign mimetype and uri.
     *
     * @param string $asset
     * @param string $group   (optional)
     * @param string $content (optional) (make it a inline asset)
     *
     * @return \Ems\Contracts\Assets\Asset
     **/
    public function newAsset($asset, $group = null, $content = '');

    /**
     * Add a callable to manually process a distinct asset
     * If the registrar has a handler for asset $asset it will not
     * add it and instead call the handler.
     * Signature is:.
     *
     * function(Registrar $registrar, $asset) {}
     *
     * Multiple callables will be added. a new callable for the same asset
     * will overwrite the previous.
     *
     * Example: on('jquery.contextMenu.js', function ($registrar, $asset, $group){
     *      $registrar->import('jquery.js');
     *      $registrar->import('jquery.contextMenu.js');
     * })
     *
     * @param string   $asset
     * @param callable $handler
     *
     * @return self
     **/
    public function on($asset, callable $handler);

    /**
     * Allows to force a position of this asset. No $asset means just "somewhere
     * at the end".
     *
     * @example $registrar->import('jquery.select.js')->after('jquery.js')
     *
     * @param string $asset (optional)
     *
     * @return self
     **/
    public function after($asset = null);

    /**
     * Allows to force a position of this asset. No $asset means before all
     * others.
     *
     * @example $registrar->import('jquery.select.js')->before('jquery.js')
     *
     * @param string $asset (optional)
     *
     * @return self
     **/
    public function before($asset = null);
}
