<?php
/**
 *  * Created by mtils on 16.07.2022 at 08:37.
 **/

namespace Ems\Auth;

/**
 * This is a placeholder for your user object. Ems is not interested what your
 * user is.
 */
class User
{
    public $id = '';
    public $email = '';

    public function __construct(array $properties=[])
    {
        $this->id = $properties['id'] ?? '';
        $this->email = $properties['email'] ?? '';
    }
}