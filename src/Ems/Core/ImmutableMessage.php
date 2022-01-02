<?php
/**
 *  * Created by mtils on 12.12.2021 at 08:22.
 **/

namespace Ems\Core;

use Ems\Contracts\Core\Message;

use function array_key_exists;
use function func_get_args;
use function is_array;

/**
 * @property-read ImmutableMessage|null previous
 * @property-read ImmutableMessage|null next
 */
class ImmutableMessage extends Message
{
    /**
     * @var ImmutableMessage|null
     */
    protected $previous;

    /**
     * @var ImmutableMessage|null
     */
    protected $next;

    public function __construct(array $attributes = [])
    {
        parent::__construct();
        $this->apply($attributes);
    }

    /**
     * Return a new instance that has $key set to $value. Pass an array for
     * multiple changed parameters.
     *
     * @param string|array $key
     * @param mixed $value (optional)
     * @return $this
     */
    public function with($key, $value=null) : ImmutableMessage
    {
        $attributes = is_array($key) ? $key : [$key => $value];
        $custom = $this->custom;
        foreach ($attributes as $key=>$value) {
            $custom[$key] = $value;
        }
        return $this->replicate([Message::POOL_CUSTOM => $custom]);
    }

    /**
     * Return a new instance without data for $key(s)
     *
     * @param string|array $key
     * @return $this
     */
    public function without($key) : ImmutableMessage
    {
        $keys = is_array($key) ? $key : func_get_args();
        $custom = $this->custom;
        foreach ($keys as $key) {
            unset($custom[$key]);
        }
        return $this->replicate([Message::POOL_CUSTOM => $custom]);
    }

    /**
     * Clone and change type.
     *
     * @param string $type
     * @return $this
     */
    public function withType(string $type) : ImmutableMessage
    {
        return $this->replicate(['type' => $type]);
    }

    /**
     * Clone and change transport.
     *
     * @param string $transport
     * @return $this
     */
    public function withTransport(string $transport) : ImmutableMessage
    {
        return $this->replicate(['transport' => $transport]);
    }

    /**
     * Clone and change envelope.
     *
     * @param array $envelope
     * @return $this
     */
    public function withEnvelope(array $envelope) : ImmutableMessage
    {
        return $this->replicate(['envelope' => $envelope]);
    }

    /**
     * Clone and change payload.
     *
     * @param $payload
     * @return $this
     */
    public function withPayload($payload) : ImmutableMessage
    {
        return $this->replicate(['payload' => $payload]);
    }

    public function __get(string $key)
    {
        switch ($key) {
            case 'previous':
                return $this->previous;
            case 'next':
                return $this->next;
        }
        return parent::__get($key);
    }

    /**
     * @param array $attributes
     * @return $this
     */
    protected function replicate(array $attributes=[]) : ImmutableMessage
    {
        $this->copyStateInto($attributes);
        $this->next = new static($attributes);
        return $this->next;
    }

    protected function apply(array $attributes)
    {
        if (isset($attributes['type'])) {
            $this->type = $attributes['type'];
        }
        if (isset($attributes['transport'])) {
            $this->transport = $attributes['transport'];
        }
        if (isset($attributes['custom'])) {
            $this->custom = $attributes['custom'];
        }
        if (isset($attributes['envelope'])) {
            $this->applyEnvelope($attributes['envelope']);
        }
        if (array_key_exists('payload', $attributes)) {
            $this->payload = $attributes['payload'];
        }
        if (isset($attributes['previous'])) {
            $this->previous = $attributes['previous'];
        }

    }

    protected function copyStateInto(array &$attributes)
    {
        if (!isset($attributes['type'])) {
            $attributes['type'] = $this->type;
        }
        if (!isset($attributes['transport'])) {
            $attributes['transport'] = $this->transport;
        }
        if (!isset($attributes['custom'])) {
            $attributes['custom'] = $this->custom;
        }
        if (!isset($attributes['envelope'])) {
            $attributes['envelope'] = $this->envelope;
        }
        if (!array_key_exists('payload', $attributes)) {
            $attributes['payload'] = $this->payload;
        }
        $attributes['previous'] = $this;
    }

    protected function applyEnvelope(array $envelope)
    {
        $this->envelope = $envelope;
    }

    protected function isAssociative($data) : bool
    {
        if (!is_array($data)) {
            return false;
        }
        foreach ($data as $key=>$value) {
            return $key !== 0;
        }
        return true;
    }
}
