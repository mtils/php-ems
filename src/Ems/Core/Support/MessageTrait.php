<?php
/**
 *  * Created by mtils on 24.08.19 at 08:19.
 **/

namespace Ems\Core\Support;


use Ems\Contracts\Core\Message;
use function is_bool;

/**
 * Trait MessageTrait
 *
 * @see Message
 *
 * @package Ems\Core\Support
 */
trait MessageTrait
{
    /**
     * @var string
     */
    protected $_type = Message::TYPE_CUSTOM;

    /**
     * @var bool|null
     */
    protected $_accepted;

    /**
     * @var string
     */
    protected $_source = Message::TRANSPORT_APP;

    /**
     * Return the type of Message this is. A http request or console input would
     * be INPUT.
     *
     * @return string
     *
     * @see self::INPUT
     */
    public function type()
    {
        return $this->_type;
    }

    /**
     * Return true if the input was accepted. If nobody accepted it this returns
     * false even it was not ignored.
     *
     * @return bool
     */
    public function isAccepted()
    {
        return is_bool($this->_accepted) && $this->_accepted;
    }

    /**
     * Return true if the input was ignored. If nobody ignored it this returns
     * false even it was not accepted.
     *
     * @return bool|null
     */
    public function isIgnored()
    {
        return is_bool($this->_accepted) && !$this->_accepted;
    }

    /**
     * Mark the message as accepted.
     *
     * @return self
     */
    public function accept()
    {
        $this->_accepted = true;
        return $this;
    }

    /**
     * Mark the message as ignored.
     *
     * @return self
     */
    public function ignore()
    {
        $this->_accepted = false;
        return $this;
    }

    /**
     * Through which channel that was sent?
     *
     * @return string
     *
     * @see self::NETWORK
     */
    public function source()
    {
        return $this->_source;
    }
}