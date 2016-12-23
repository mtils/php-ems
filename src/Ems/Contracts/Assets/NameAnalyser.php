<?php

namespace Ems\Contracts\Assets;

/**
 * The NameAnalyser tells the Registrar what group a asset belongs to
 * and what mimetype it has.
 **/
interface NameAnalyser
{
    /**
     * Guess the mimetype of the passed assetName and group
     * The standard was is to extract the file extension of assetName
     * and return a mimetype of this extension.
     *
     * @param string $assetName
     * @param string $groupName (optional)
     *
     * @throws NotFound
     *
     * @return string
     **/
    public function guessMimeType($assetName, $groupName = null);

    /**
     * Guess the group of the passed asset and groupName.
     *
     * @param string $assetName
     *
     * @throws NotFound
     *
     * @return string
     **/
    public function guessGroup($assetName);
}
