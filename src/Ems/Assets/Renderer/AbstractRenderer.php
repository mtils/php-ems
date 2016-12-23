<?php

namespace Ems\Assets\Renderer;

use Ems\Contracts\Assets\Asset as AssetContract;
use Ems\Contracts\Core\Renderer;
use Ems\Contracts\Core\Renderable;
use Ems\Contracts\Assets\Renderable as BaseAsset;
use Ems\Contracts\Assets\Asset;

abstract class AbstractRenderer implements Renderer
{
    /**
     * Fill this array to mark the contained mimeTypes as
     * supported.
     *
     * @var array
     **/
    protected $mimeTypes = [];

    /**
     * @var string
     **/
    protected $forcedGroup = '';

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Core\Renderable
     *
     * @return bool
     **/
    public function canRender(Renderable $item)
    {
        if (!$item instanceof BaseAsset) {
            return false;
        }

        if ($this->forcedGroup && $item->group() != $this->forcedGroup) {
            return false;
        }

        return in_array($item->mimeType(), $this->mimeTypes);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Core\Renderable $item
     *
     * @return string
     **/
    public function render(Renderable $item)
    {
        $output = '';
        $nl = '';
        $group = $item->group();

        if ($item instanceof AssetContract) {
            return $this->renderAsset($item, $group);
        }

        foreach ($item as $asset) {
            $output .= $nl.$this->renderAsset($asset, $group);
            $nl = "\n";
        }

        return $output;
    }

    /**
     * Force this renderer to only render collections with group $group.
     *
     * @param string $group
     *
     * @return self
     **/
    public function forceGroup($group)
    {
        $this->forcedGroup = $group;

        return $this;
    }

    /**
     * Return ths forced group (or an empty string if no specific group is forced).
     *
     * @return string
     **/
    public function forcedGroup()
    {
        return $this->forcedGroup;
    }

    /**
     * Renders one asset.
     *
     * @param \Ems\Contracts\Assets\Asset $asset
     * @param string                      $group
     *
     * @return string
     **/
    protected function renderAsset(Asset $asset, $group)
    {
        return $asset->isInline() ? $this->renderInline($asset, $group) : $this->renderExternal($asset, $group);
    }

    /**
     * Renders an external asset.
     *
     * @param \Ems\Contracts\Assets\Asset $asset
     * @param string                      $group
     *
     * @return string
     **/
    protected function renderExternal(Asset $asset, $group)
    {
        return '<!-- asset external'.$this->renderAttributes(['href' => $asset->uri()], $asset->attributes()).' -->';
    }

    /**
     * Renders an inline asset.
     *
     * @param \Ems\Contracts\Assets\Asset $asset
     * @param string                      $group
     *
     * @return string
     **/
    protected function renderInline(Asset $asset, $group)
    {
        return '<!-- asset inline content="'.$asset->content().'" -->';
    }

    protected function renderAttributes(array $attributes, array $additionalAttributes = [])
    {
        $renderAttributes = array_merge($attributes, $additionalAttributes);

        if (!count($renderAttributes)) {
            return '';
        }

        $rows = [];

        foreach ($renderAttributes as $key => $value) {
            $rows[] = "{$key}=\"".trim(strip_tags(htmlspecialchars("$value", ENT_QUOTES))).'"';
        }

        return implode(' ', $rows);
    }
}
