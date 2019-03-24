<?php
/**
 *  * Created by mtils on 09.09.18 at 10:38.
 **/

namespace Ems\Core\Storages;


use Ems\Contracts\Core\IdGenerator;
use Ems\Contracts\Core\PushableStorage;
use Ems\Contracts\Core\Storage;
use Ems\Core\IdGenerator\IncrementingIdGenerator;

class PushableProxyStorage extends AbstractProxyStorage implements PushableStorage
{
    /**
     * @var IdGenerator
     */
    protected $idGenerator;

    /**
     * Determine if this storage should pass the value to be added to the id
     * generator.
     *
     * @var bool
     */
    protected $passSalt = true;

    public function __construct(Storage $storage, IdGenerator $idGenerator=null)
    {
        parent::__construct($storage);
        $this->idGenerator = $idGenerator ?: new IncrementingIdGenerator();
        // Pass salt by default only if the generator does not create integers
        $this->passSalt = $this->idGenerator->idType() != 'int';

    }

    /**
     * @inheritdoc
     *
     * @param mixed $value
     *
     * @return string|int
     */
    public function offsetPush($value)
    {

        $keys = $this->storage->keys();

        $nextId = $this->idGenerator->until(function ($id) use ($keys) {
            return !$keys->contains($id);
        })->generate($this->passSalt ? $value : null);

        $this->storage->offsetSet($nextId, $value);

        return $nextId;
    }

}