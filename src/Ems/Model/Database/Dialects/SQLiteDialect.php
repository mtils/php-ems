<?php

namespace Ems\Model\Database\Dialects;

use Ems\Contracts\Model\Database\NativeError;
use Ems\Contracts\Model\Database\SQLException;
use Ems\Model\Database\SQLConstraintException;
use Ems\Model\Database\SQLDeniedException;
use Ems\Model\Database\SQLExceededException;
use Ems\Model\Database\SQLIOException;
use Ems\Model\Database\SQLLockException;
use Exception;
use InvalidArgumentException;
use function str_replace;


class SQLiteDialect extends AbstractDialect
{

    /**
     * SQLite error codes (from source)
     **/
    const OK            = 0;   /* Successful result */
    const ERROR         = 1;   /* SQL error or missing database */
    const INTERNAL      = 2;   /* An internal logic error in SQLite */
    const PERM          = 3;   /* Access permission denied */
    const ABORT         = 4;   /* Callback routine requested an abort */
    const BUSY          = 5;   /* The database file is locked */
    const LOCKED        = 6;   /* A table in the database is locked */
    const NOMEM         = 7;   /* A malloc() failed */
    const READONLY      = 8;   /* Attempt to write a readonly database */
    const INTERRUPT     = 9;   /* Operation terminated by sqlite_interrupt() */
    const IOERR         = 10;   /* Some kind of disk I/O error occurred */
    const CORRUPT       = 11;   /* The database disk image is malformed */
    const NOTFOUND      = 12;   /* (Internal Only) Table or record not found */
    const FULL          = 13;   /* Insertion failed because database is full */
    const CANTOPEN      = 14;   /* Unable to open the database file */
    const PROTOCOL      = 15;   /* Database lock protocol error */
    const EMPTY_TABLE   = 16;   /* ORIGINAL: EMPTY (Internal Only) Database table is empty */
    const SCHEMA        = 17;   /* The database schema changed */
    const TOOBIG        = 18;   /* Too much data for one row of a table */
    const CONSTRAINT    = 19;   /* Abort due to contraint violation */
    const MISMATCH      = 20;   /* Data type mismatch */
    const MISUSE        = 21;   /* Library used incorrectly */
    const NOLFS         = 22;   /* Uses OS features not supported on host */
    const AUTH          = 23;   /* Authorization denied */
    const NO_DB         = 26;   /* File is not a database */
    const ROW           = 100;  /* sqlite_step() has another row ready */
    const DONE          = 101;  /* sqlite_step() has finished executing */

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function name()
    {
        return 'sqlite';
    }

    /**
     * Return the timestamp format of this database.
     *
     * @return string
     **/
    public function timeStampFormat()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * {@inheritdoc}
     *
     * @param NativeError $error
     * @param Exception  $original (optional)
     *
     * @return SQLException
     **/
    public function createException(NativeError $error, Exception $original = null)
    {
        switch ($error->code) {
            case self::PERM:
            case self::READONLY:
            case self::AUTH:
            case self::CANTOPEN:
                return new SQLDeniedException($error->message, $error, 0, $original);
//             case self::ABORT: Cannot reproduce this
                // Canceled
//                 echo "\nABORT ERROR $error->code";
//                 break;
            case self::BUSY:
            case self::LOCKED:
            case self::PROTOCOL:
            case self::ROW:
                return new SQLLockException($error->message, $error, 0, $original);
            case self::IOERR:
            case self::CORRUPT:
            case self::NO_DB:
                return new SQLIOException($error->message, $error, 0, $original);
            case self::NOMEM:
            case self::FULL:
            case self::TOOBIG:
                // Cannot reproduce this in ci
                // @codeCoverageIgnoreStart
                return new SQLExceededException($error->message, $error, 0, $original);
                // @codeCoverageIgnoreEnd
            case self::CONSTRAINT:
            case self::MISMATCH:
            case self::MISUSE:
                return new SQLConstraintException($error->message, $error, 0, $original);

        }

        return parent::createException($error, $original);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function quoteString($string)
    {
        return "'" . str_replace("'", "''", $string) . "'";
    }

    /**
     * @param string $name
     * @param string $type
     *
     * @return string
     */
    protected function quoteName($name, $type = 'name')
    {
        if ($type != 'name') {
            throw new InvalidArgumentException("type has to be either string|name, not $type");
        }
        return '"' . str_replace('"', '""', $name) . '"';
    }


}
