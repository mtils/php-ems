<?php
/**
 *  * Created by mtils on 17.12.17 at 13:45.
 **/

namespace Ems\Contracts\Model;

use Ems\Core\Collections\TypeEnforcedList;
use Ems\Core\Exceptions\UnsupportedParameterException;


class AttachmentList extends TypeEnforcedList
{

    /**
     * AttachmentList constructor.
     *
     * @param array $attachments
     */
    public function __construct($attachments=[])
    {
        parent::__construct();

        $this->setForcedType(Attachment::class);

        foreach ($attachments as $attachment) {
            $this->addItem($attachment);
        }
    }

    /**
     * Filter the attachments by role, contentType, type.
     *
     * @param string $key
     * @param string $value
     *
     * @return self
     */
    public function where($key, $value)
    {
        $filter = $this->createFilter($key, $value);
        return new static(array_filter($this->source, $filter));
    }

    /**
     * Create a filter Closure to realize the where method.
     *
     * @param string $key
     * @param string $value
     *
     * @return \Closure
     */
    protected function createFilter($key, $value)
    {
        if ($key == 'role') {
            return function (Attachment $attachment) use ($value) {
                return $attachment->getRole() == $value;
            };
        }

        if ($key == 'mimetype' || $key == 'type') {
            return function (Attachment $attachment) use ($value) {
                return $attachment->is($value);
            };
        }

        throw new UnsupportedParameterException("Filtering by key '$key' is not supported.'");
    }

}