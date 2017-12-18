<?php
/**
 *  * Created by mtils on 17.12.17 at 14:43.
 **/

namespace Ems\Model;


use Ems\Contracts\Core\Containers\Size;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Model\Attachment;
use Ems\Core\Url;
use function is_array;

class OrmAttachment extends OrmObject implements Attachment
{

    /**
     * @var Size
     */
    protected $size;

    /**
     * @var Url
     */
    protected $url;

    /**
     * @var array
     */
    protected $sizes = [];

    /**
     * @var array
     */
    protected $sizeUrls = [];

    /**
     * @var array
     */
    protected $sizeCache;

    /**
     * @var array
     */
    protected $optimalSizeCache = [];

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function getName()
    {
        return $this->__get('name');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function resourceName()
    {
        return 'attachments';
    }

    /**
     * {@inheritdoc}
     *
     * @param int|Size $width
     * @param int      $height (optional)
     * @return Url
     */
    public function getUrl($width=null, $height=null)
    {

        if ($width || $height) {
            return $this->urlForSize($width, $height);

        }

        if (!$this->url) {
            $this->url = new Url($this->__get('url'));
        }

        return $this->url;

    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getMimetype()
    {
        return $this->__get('mimetype');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRole()
    {
        return $this->__get('role');
    }

    /**
     * {@inheritdoc}
     *
     * @param string $type
     *
     * @return bool
     */
    public function is($type)
    {
        $myMimetype = $this->getMimetype();

        if (strpos($type, '/')) {
            return strtolower($myMimetype) == strtolower($type);
        }

        $prefix = strtolower(explode('/', $myMimetype)[0]);

        return strtolower($type) == $prefix;
    }

    /**
     * Return the size of this attachment.
     *
     * @return Size
     */
    public function getSize()
    {

        if ($this->size) {
            return $this->size;
        }

        $this->size = new Size($this->__get('width'), $this->__get('height'));

        return $this->size;
    }

    /**
     * {@inheritdoc}
     *
     * @return Size[]
     */
    public function sizes()
    {
        return array_merge([$this->getSize()], array_values($this->sizes));
    }

    /**
     * Add an url to a different size of this attachment.
     *
     * @param array|Size    $size
     * @param UrlContract   $url
     *
     * @return $this
     */
    public function addSize($size, UrlContract $url)
    {
        $size = is_array($size) ? new Size($size[0], $size[1]) : $size;

        if ($size->equals($this->getSize())) {
            return $this;
        }

        $key = "$size";

        $this->sizes[$key] = $size;
        $this->sizeUrls[$key] = $url;

        return $this;
    }

    /**
     * Remove a previously added size.
     *
     * @param array|Size $size
     *
     * @return $this
     */
    public function removeSize($size)
    {
        $size = is_array($size) ? new Size($size[0], $size[1]) : $size;
        $key = "$size";

        if (isset($this->sizes[$key])) {
            unset($this->sizes[$key]);
        }

        if (isset($this->sizeUrls[$key])) {
            unset($this->sizeUrls[$key]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Reimplemented to delete some cached entries.
     *
     * @param string $name
     *
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        parent::__set($name, $value);

        if ($name == 'width' || $name == 'height') {
            $this->size = null;
        }

        if ($name == 'url') {
            $this->url = null;
        }

    }

    /**
     * Set some defaults into the array.
     *
     * @param array $attributes
     * @param bool $isFromStorage
     */
    protected function init(array &$attributes, $isFromStorage)
    {
        if (!isset($attributes['role'])) {
            $attributes['role'] = Attachment::DISPLAY;
        }
    }

    /**
     * Get the attachment in size $hint;
     *
     * @param int|Size $width
     * @param int      $height (optional)
     *
     * @return Url
     */
    protected function urlForSize($width, $height=null)
    {

        if (!$this->sizes) {
            return $this->getUrl();
        }

        $targetSize = $width instanceof Size ? $width : new Size($width, $height);
        $targetKey = "$targetSize";

        if (isset($this->optimalSizeCache[$targetKey])) {
            return $this->optimalSizeCache[$targetKey];
        }

        $optimalSize = $targetSize->findBest($this->sizes());

        if ($this->getSize()->equals($optimalSize)) {
            $this->optimalSizeCache[$targetKey] = $this->getUrl();
            return $this->optimalSizeCache[$targetKey];
        }

        $optimalKey = "$optimalSize";

        if (isset($this->sizeUrls[$optimalKey])) {
            $this->optimalSizeCache[$targetKey] = $this->sizeUrls[$optimalKey];
            return $this->optimalSizeCache[$targetKey];
        }

        return $this->getUrl();

    }
}