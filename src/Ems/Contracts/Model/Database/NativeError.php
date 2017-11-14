<?php


namespace Ems\Contracts\Model\Database;


/**
 * This class contains informations to estimate an error.
 **/
class NativeError
{

    /**
     * Return the executed query.
     *
     * @var string
     **/
    public $query = '';

    /**
     * The ANSI SQL 92 error code.
     *
     * @var string
     **/
    public $sqlstate = 'HY000';

    /**
     * The native error code of the database backend.
     *
     * @var string|int
     **/
    public $code = 0;

    /**
     * The native error message of the database backend.
     *
     * @var string
     **/
    public $message = '';

    /**
     * Fill the object.
     *
     * @param array $error (optional)
     **/
    public function __construct(array $error=[])
    {
        foreach ($error as $key=>$value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
