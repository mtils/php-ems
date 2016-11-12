<?php

namespace Ems\Cache;

use Ems\Contracts\Cache\Categorizer;
use Ems\Core\Patterns\TraitOfResponsibility;
use DateTime;
use Ems\Core\Exceptions\HandlerNotFoundException;

class CategorizerChain implements Categorizer
{
    use TraitOfResponsibility;

    /**
     * @var string
     **/
    protected $defaultLifetime = '1 day';

    /**
     * @var \DateTime
     **/
    protected $defaultUntil;

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     *
     * @return string|null $id
     **/
    public function key($value)
    {
        foreach ($this->candidates as $categorizer) {
            $result = $categorizer->key($value);
            if ($result !== null) {
                return $result;
            }
        }

        $name = is_object($value) ? get_class($value) : gettype($value);

        throw new HandlerNotFoundException("No handler found to provide an id for $name");
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     *
     * @return array|null
     **/
    public function tags($value)
    {
        foreach ($this->candidates as $categorizer) {
            $result = $categorizer->tags($value);
            if ($result !== null) {
                return $result;
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     *
     * @return \DateTime|null
     **/
    public function lifetime($value)
    {
        foreach ($this->candidates as $categorizer) {
            $result = $categorizer->lifetime($value);
            if ($result !== null) {
                return $result;
            }
        }

        return $this->defaultLifetime instanceof DateTime
                ? $this->defaultLifetime
                : (new DateTime())->modify($this->defaultLifetime);
    }

    public function getDefaultLifetime()
    {
        return $this->defaultLifetime;
    }

    public function setDefaultLifetime($lifetime)
    {
        $this->defaultLifetime = $lifetime;

        return $this;
    }
}
