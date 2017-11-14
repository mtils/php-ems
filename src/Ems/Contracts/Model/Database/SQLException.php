<?php

namespace Ems\Contracts\Model\Database;


use RuntimeException;
use Exception;

class SQLException extends RuntimeException
{
    /**
     * @var string
     **/
    protected $query = '';

    /**
     * @var string
     **/
    protected $sqlState = 'HY000';

    /**
     * @var int|string
     **/
    protected $nativeCode = 0;

    /**
     * @var string
     **/
    protected $nativeMessage = '';

    /**
     * @var NativeError
     **/
    protected $nativeError;

    /**
     * @param string                $message (optional)
     * @param string|NativeError    $queryOrError   (optional)
     * @param int                   $code    (optional)
     * @param Exception             $previous (optional)
     **/
    public function __construct($message='', $queryOrError='', $code=0, Exception $previous=null)
    {

        $this->nativeError = $queryOrError instanceof NativeError ?
                             $queryOrError :
                             new NativeError(['query' => $queryOrError]);

        parent::__construct($this->buildMessage($message, $this->query()), 0, $previous);

    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function query()
    {
        return $this->nativeError->query;
    }

    /**
     * {@inheritdoc}
     *
     * @return string (default: HY000)
     **/
    public function sqlState()
    {
        return $this->nativeError->sqlState;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     **/
    public function nativeCode()
    {
        return $this->nativeError->code;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function nativeMessage()
    {
        return $this->nativeError->message;
    }

    public function nativeError()
    {
        return $this->nativeError;
    }

    /**
     * Fill the exception by an array. The following keys are supported:
     * sqlstate, code, msg
     *
     * @param array $error
     *
     * @return self
     **/
    public function fill(array $error)
    {
        $this->setSqlState(isset($error['sqlstate']) ? $error['sqlstate'] : 'HY000')
             ->setNativeCode(isset($error['code']) ? $error['code'] : 0)
             ->setNativeMessage(isset($error['msg']) ? $error['msg'] : '');

        return $this;

    }

    /**
     * Appends the $query to the message and formats it for parent.
     *
     * @param string $message
     * @param string $query
     *
     * @return string
     **/
    protected function buildMessage($message, $query)
    {
        if (!$query) {
            return $message;
        }

        if (!$message) {
            substr("Error in QUERY: $query", 0, 1024);
        }

        return substr("$message (QUERY: $query)", 0, 1024);
    }

}
