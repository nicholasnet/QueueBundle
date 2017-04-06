<?php

namespace IdeasBucket\QueueBundle\Exception;

use Psr\Log\LoggerInterface;

/**
 * Class ErrorHandler
 *
 * @package IdeasBucket\QueueBundle\Exceptions
 */
class ErrorHandler
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Create a new exception handler instance.
     *
     * @param  LoggerInterface  $logger
     */
    public function __construct(LoggerInterface  $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $e
     *
     * @throws \Exception
     */
    public function report(\Exception $e)
    {
        if ($this->logger !== null) {

            $this->logger->error($e->getMessage());
        }
    }
}