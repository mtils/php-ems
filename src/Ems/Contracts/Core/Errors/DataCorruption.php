<?php

namespace Ems\Contracts\Core\Errors;

/**
 * This is an empty interface for exceptions.
 * The exception will mark that some sort of data corruption has occured. If
 * foreign keys are not matching, saved data was corrupted, a checkum has failed
 * an exception of this interface should be thrown. It is not meant for invalid
 * data in user requests, only for persisted data.
 **/
interface DataCorruption
{
}
