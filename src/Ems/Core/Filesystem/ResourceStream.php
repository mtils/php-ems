<?php
/**
 *  * Created by mtils on 23.10.18 at 09:03.
 **/

namespace Ems\Core\Filesystem;


use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Core\Type;
use function is_resource;

class ResourceStream extends AbstractStream
{
    /**
     * ResourceStream constructor.
     *
     * @param resource $resource
     * @param bool     $lock (default:false)
     */
    public function __construct($resource, $lock=false)
    {
        if (!is_resource($resource)) {
            throw new TypeException("Resource must be a resource not " . Type::of($resource));
        }
        $this->assignResource($resource);
        if ($lock) {
            $this->lock($lock);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     * @link https://php.net/manual/en/language.oop5.decon.php
     */
    public function __destruct()
    {
        // Don't close passed handles
    }

}