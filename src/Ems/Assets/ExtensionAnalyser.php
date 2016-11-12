<?php

namespace Ems\Assets;

use Ems\Contracts\Assets\NameAnalyser;
use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\MimeTypeProvider;
use Ems\Core\Exceptions\DetectionFailedException;

/**
 * The ExtensionAnalyser calculates the group and mimetype by the passed
 * extension of the passed file.
 **/
class ExtensionAnalyser implements NameAnalyser
{
    /**
     * @var \Ems\Contracts\Core\Filesystem
     **/
    protected $files;

    /**
     * @var \Ems\Contracts\Core\MimeTypeProvider
     **/
    protected $mimeTypes;

    /**
     * @param \Ems\Contracts\Core\Filesystem       $files
     * @param \Ems\Contracts\Core\MimeTypeProvider $mimeTypes
     **/
    public function __construct(FileSystem $files, MimeTypeProvider $mimeTypes)
    {
        $this->files = $files;
        $this->mimeTypes = $mimeTypes;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $assetName
     * @param string $groupName (optional)
     *
     * @return string
     *
     * @throws NotFound
     **/
    public function guessMimeType($assetName, $groupName = null)
    {
        if ($mimeType = $this->mimeTypes->typeOfName($assetName)) {
            return $mimeType;
        }

        if (!$groupName) {
            throw new DetectionFailedException("Cannot detect mimetype in asset $assetName and group $groupName");
        }

        if ($mimeType = $this->mimeTypes->typeOfName($groupName)) {
            return $mimeType;
        }

        throw new DetectionFailedException("Cannot detect mimetype in asset $assetName and group $groupName");
    }

    /**
     * {@inheritdoc}
     *
     * @param string $assetName
     *
     * @return string
     *
     * @throws NotFound
     **/
    public function guessGroup($assetName)
    {
        if ($extension = $this->files->extension($assetName)) {
            return $extension;
        }

        throw new DetectionFailedException("Cannot detect group of asset $assetName");
    }
}
