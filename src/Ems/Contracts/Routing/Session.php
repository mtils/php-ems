<?php
/**
 *  * Created by mtils on 15.01.2022 at 17:44.
 **/

namespace Ems\Contracts\Routing;

use Ems\Contracts\Core\Storage;

interface Session extends Storage
{
    /**
     * Get the session ID
     *
     * @return string
     */
    public function getId() : string;

    /**
     * Set the id that is used by the handler to load the data.
     *
     * @param string $id
     * @return Session
     */
    public function setId(string $id) : Session;

    /**
     * Start the session. This should not be needed to be called manually.
     * It should start when somebody tries to access data of the session.
     *
     * @return bool
     */
    public function start() : bool;

    /**
     * Return true if the session was started.
     *
     * @return bool
     */
    public function isStarted() : bool;

}