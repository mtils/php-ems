<?php


namespace Ems\Assets\Renderer;

use Ems\Contracts\Assets\Asset;


class JavascriptRenderer extends AbstractRenderer
{

    protected $mimeTypes = ['application/javascript'];

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Assets\Asset $asset
     * @param string $group
     * @return string
     **/
    protected function renderExternal(Asset $asset, $group)
    {
        return '<script ' . $this->renderAttributes(['src' => $asset->uri()], $asset->attributes()) . ' "></script>';
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Assets\Asset $asset
     * @return string
     **/
    protected function renderInline(Asset $asset, $group)
    {
        return "<script>\n" . $asset->content() . "\n</script>";
    }

}
