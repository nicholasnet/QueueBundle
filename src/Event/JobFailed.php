<?php

namespace IdeasBucket\QueueBundle\Event;

use IdeasBucket\QueueBundle\Job\JobsInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class JobFailed
 *
 * @package IdeasBucket\QueueBundle\Event
 */
class JobFailed extends Event
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The job instance.
     *
     * @var JobsInterface
     */
    public $job;

    /**
     * The exception that caused the job to fail.
     *
     * @var \Exception
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  JobsInterface  $job
     * @param  \Exception  $exception
     */
    public function __construct($connectionName, $job, $exception)
    {
        $this->job = $job;
        $this->exception = $exception;
        $this->connectionName = $connectionName;
    }
}
