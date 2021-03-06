<?php

namespace Ems\Core;

use Ems\Contracts\Core\AllProvider;
use Ems\Contracts\Core\TextProvider;
use Ems\Contracts\Core\Named;
use Ems\Contracts\Core\Identifiable;
use Ems\Contracts\Core\AppliesToResource;
use Ems\Core\Exceptions\ResourceNotFoundException;

class GenericResourceProvider extends AnythingProvider implements AppliesToResource
{
    /**
     * Change this to a class or interface name
     *
     * @var string
     **/
    protected $forceType = Named::class;

    /**
     * @var TextProvider
     **/
    protected $texts;

    /**
     * @var string
     **/
    protected $resourceName;

    /**
     * @var array
     **/
    protected $replace = [];

    /**
     * @param TextProvider $texts (optional)
     **/
    public function __construct(TextProvider $texts = null)
    {
        $this->texts = $texts;
    }

    /**
     * Add a identifiable object or just an id.
     *
     * @param mixed $identifiable
     *
     * @return $this
     **/
    public function add($identifiable)
    {
        if ($identifiable instanceof Named) {
            return $this->set($identifiable->getId(), $identifiable);
        }

        $id = $identifiable instanceof Identifiable ? $identifiable->getId() : $identifiable;
        $title = $this->getTitleFromProvider($id);
        $resourceName = $identifiable instanceof AppliesToResource ? $identifiable->resourceName : $this->resourceName;

        return $this->set($id, $this->createItem($id, $title, $resourceName));
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     *
     * @see AppliesToResource
     **/
    public function resourceName()
    {
        return $this->resourceName;
    }

    /**
     * Set the resource name.
     *
     * @param string $resourceName
     *
     * @return self
     **/
    public function setResourceName($resourceName)
    {
        $this->resourceName = $resourceName;

        return $this;
    }

    /**
     * Replace this chars in translation keys. This is usefull if you
     * have for example template names with dots in it and need translation
     * keys with dots so dots in translation keys are reserved. I usually replace
     * dots for translation keys in /.
     *
     * @param string $search
     * @param string $replace
     *
     * @return self
     **/
    public function replaceInKeys($search, $replace)
    {
        $this->replace[$search] = $replace;

        return $this;
    }

    /**
     * Creat an item. Overwrite this method to create different items.
     *
     * @param mixed  $id
     * @param string $title
     * @param string $resourceName
     *
     * @return Named
     **/
    protected function createItem($id, $title, $resourceName)
    {
        return new NamedObject($id, $title, $resourceName);
    }

    /**
     * Get the title from TextProvider if one set.
     *
     * @param mixed $itemId
     *
     * @return string
     **/
    protected function getTitleFromProvider($itemId)
    {
        $itemId = $this->escapeId($itemId);
        if (!$this->texts) {
            return $itemId;
        }

        return $this->texts->get($itemId);
    }

    /**
     * Escape the id for safe usage in translation keys.
     *
     * @param string $id
     *
     * @return string
     **/
    protected function escapeId($id)
    {
        if (!$this->replace) {
            return $id;
        }

        return str_replace(array_keys($this->replace), array_values($this->replace), $id);
    }
}
