<?php
/**
 *  * Created by mtils on 19.12.2021 at 12:05.
 **/

namespace Ems\Core;

use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\StringableTrait;
use Ems\Contracts\Core\Message as AbstractMessage;

use Ems\Contracts\Core\Type;

use function func_num_args;

/**
 * This is a default response. It is an ImmutableMessage that is
 * returned by the application fulfilling a request.
 *
 * @property-read int status
 * @property-read string statusMessage
 * @property-read string contentType
 */
class Response extends ImmutableMessage implements Stringable
{
    use StringableTrait;

    /**
     * @var int
     */
    protected $status = 0;

    /**
     * @var string
     */
    protected $statusMessage = '';

    /**
     * @var string
     */
    protected $contentType = '';

    /**
     * Create a new response.
     * Pass only one assoziative array parameter to fill all the properties (like in message)
     * Pass more than one parameter to set payload, envelope and status.
     * If $data is not an associative array it will never be taken as attributes.
     *
     * @param mixed $attributesOrPayload
     * @param array $envelope
     * @param int $status
     */
    public function __construct($attributesOrPayload = null, array $envelope=[], int $status=0)
    {
        $this->type = AbstractMessage::TYPE_OUTPUT;
        $this->status = $status;
        if (!$this->contentType) {
            $this->contentType = ManualMimeTypeProvider::$fallbackMimeType;
        }

        if (func_num_args() < 2) {
            $attributes = $this->isAssociative($attributesOrPayload) ? $attributesOrPayload : ['payload' => $attributesOrPayload];
            parent::__construct($attributes);
            return;
        }

        $attributes = [
            'payload' => $attributesOrPayload,
            'envelope' => $envelope,
            'status'   => $status
        ];

        if ($this->isAssociative($attributesOrPayload)) {
            $attributes['custom'] = $attributesOrPayload;
        }

        parent::__construct($attributes);

    }

    public function withStatus($code, $reasonPhrase='')
    {
        $message = func_num_args() == 2 ? $reasonPhrase : $this->statusMessage;
        return $this->replicate(['status' => $code, 'statusMessage' => $message]);
    }

    public function withContentType(string $contentType)
    {
        return $this->replicate(['contentType' => $contentType]);
    }

    public function __get(string $key)
    {
        if ($key == 'status') {
            return $this->status;
        }
        if ($key == 'statusMessage') {
            return $this->statusMessage;
        }
        if ($key == 'contentType') {
            return $this->contentType;
        }
        return parent::__get($key);
    }

    /**
     * @return string
     */
    public function toString()
    {
        if (Type::isStringable($this->payload)) {
            return (string)$this->payload;
        }
        return '';
    }

    protected function apply(array $attributes)
    {
        if (isset($attributes['status'])) {
            $this->status = $attributes['status'];
        }
        if (isset($attributes['statusMessage'])) {
            $this->statusMessage = $attributes['statusMessage'];
        }
        if (isset($attributes['contentType'])) {
            $this->contentType = $attributes['contentType'];
        }

        parent::apply($attributes);
    }

    protected function copyStateInto(array &$attributes)
    {
        if (!isset($attributes['status'])) {
            $attributes['status'] = $this->status;
        }
        if (!isset($attributes['statusMessage'])) {
            $attributes['statusMessage'] = $this->statusMessage;
        }
        if (!isset($attributes['contentType'])) {
            $attributes['contentType'] = $this->contentType;
        }
        parent::copyStateInto($attributes);
    }

}