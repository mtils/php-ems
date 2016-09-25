<?php


namespace Ems\Assets\Renderer;

use Ems\Contracts\Assets\Asset;


class CssRenderer extends AbstractRenderer
{

    protected $mimeTypes = ['text/css'];

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Assets\Asset $asset
     * @param string $group
     * @return string
     **/
    protected function renderExternal(Asset $asset, $group)
    {
        $attributes = [
            'rel'   => 'stylesheet',
            'type'  => $asset->mimeType(),
            'href'  => $asset->uri()
        ];
        return '<link ' . $this->renderAttributes($attributes, $asset->attributes()) . ' />';
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Assets\Asset $asset
     * @param string $group
     * @return string
     **/
    protected function renderInline(Asset $asset, $group)
    {
        return "<style>\n" . $asset->content() . "\n</style>";
    }

}
