<?php
/**
 *  * Created by mtils on 17.12.17 at 13:42.
 **/

namespace Ems\Contracts\Model;


use Ems\Contracts\Core\Containers\Size;
use Ems\Contracts\Core\Named;
use Ems\Contracts\Core\Url;

interface Attachment extends Named
{
    /**
     * This attachment is used to display something.
     * Like a random product image. This is the default.
     *
     * @var string
     */
    const DISPLAY = 'display';

    /**
     * This attachment is used as a front cover (main image)
     * of something.
     *
     * @var string
     */
    const FRONT_COVER = 'cover';

    /**
     * This attachment is used to display an alternate view
     * of something. (like birds view)
     *
     * @var string
     */
    const ALTERNATE_VIEW = 'alternate_view';

    /**
     * This attachment is a sizing table (for e.g. clothes)
     *
     * @var string
     */
    const SIZING_TABLE = 'sizing_table';

    /**
     * This attachment is the manual of a product.
     *
     * @var string
     */
    const MANUAL = 'manual';

    /**
     * This attachment is used to display (geographic) location
     * of something.
     *
     * @var string
     */
    const LOCATION = 'location';

    /**
     * Return the url to this attachment.
     *
     * @param int|Size $width
     * @param int      $height (optional)
     * @return Url
     */
    public function getUrl($width=null, $height=null);

    /**
     * Get the mimetype of this attachment.
     *
     * @return string
     */
    public function getMimetype();

    /**
     * Get the role of this attachment. These are all the constants of this interface.
     *
     * @return string
     */
    public function getRole();

    /**
     * Do some matching if the mimetype matches $type.
     * If you ask for e.g. 'image' it will match image/jpeg and so on.
     * If you ask for image/jpeg it has to match exact.
     *
     * @param string $type
     *
     * @return bool
     */
    public function is($type);

    /**
     * Return the size of this attachment.
     *
     * @return Size
     */
    public function getSize();

    /**
     * Return an array of available sizes for that attachment.
     * Makes only sense with images or videos.
     * Every size is an array with two entries: width and height.
     *
     * @return Size[]
     */
    public function sizes();

}