<?php
/**
 *  * Created by mtils on 16.01.2022 at 13:23.
 **/

namespace Ems\Routing\SessionHandler;

use ArrayAccess;
use SessionHandlerInterface;
use UnexpectedValueException;

use function is_array;
use function is_iterable;

class ArraySessionHandler implements SessionHandlerInterface
{
    /**
     * @var array|ArrayAccess
     */
    protected $data;

    /**
     * Lifetime in minutes
     *
     * @var int
     */
    protected $lifeTime = 60;

    /**
     * @param ArrayAccess|array $array
     */
    public function __construct(&$array)
    {
        $this->setArray($array);
    }

    /**
     * @param $path
     * @param $name
     * @return bool
     */
    public function open($path, $name) : bool
    {
        return true;
    }

    public function close() : bool
    {
        return true;
    }

    public function read($id)
    {
        if (!isset($this->data[$id])) {
            return '';
        }
        $session = $this->data[$id];
        if ($this->isExpired($session)) {

        }
        if(!$this->isExpired($session)) {
            return $session['data'];
        }
        return '';
    }

    public function write($id, $data) : bool
    {
        $this->data[$id] = [
            'data'      => $data,
            'updated'   => $this->now()
        ];
        return true;
    }

    public function destroy($id) : bool
    {
        if (isset($this->data[$id])) {
            unset($this->data[$id]);
        }
        return true;
    }

    public function gc($max_lifetime)
    {
        if (!is_iterable($this->data)) {
            return false;
        }
        $deleted = 0;
        $minUpdated = $this->now() - (60*$this->lifeTime);

        foreach ($this->data as $sessionId=>$data) {
            if (isset($data['updated']) && $data['updated'] < $minUpdated) {
                unset($this->data[$sessionId]);
                $deleted++;
            }
        }
        return $deleted;
    }

    public function toArray() : array
    {
        return is_array($this->data) ? $this->data : [];
    }

    /**
     * @return int
     */
    public function getLifeTime(): int
    {
        return $this->lifeTime;
    }

    /**
     * @param int $lifeTime
     * @return self
     */
    public function setLifeTime(int $lifeTime): ArraySessionHandler
    {
        $this->lifeTime = $lifeTime;
        return $this;
    }

    protected function setArray(&$array)
    {
        if (!$array instanceof ArrayAccess && !is_array($array)) {
            throw new UnexpectedValueException('Data has to be array or ArrayAccess');
        }
        $this->data = &$array;
    }

    /**
     * @return int
     */
    protected function now() : int
    {
        return time();
    }

    /**
     * @param array $session
     * @return bool
     */
    protected function isExpired(array $session) : bool
    {
        if(!isset($session['updated']) || !$session['updated']) {
            return false;
        }
        $expiresAt = $session['updated'] + (60*$this->lifeTime);
        return $this->now() > $expiresAt;
    }
}