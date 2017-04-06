<?php

namespace IdeasBucket\QueueBundle\Exception;

use Exception;

/**
 * Class MaxAttemptsExceededException
 *
 * @package IdeasBucket\QueueBundle\Exception
 */
class MaxAttemptsExceededException extends \RuntimeException
{
    public function __construct(
        $message = 'A queued job has been attempted too many times. The job may have previously timed out.',
        $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}