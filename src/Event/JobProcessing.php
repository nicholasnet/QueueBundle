<?php

namespace IdeasBucket\QueueBundle\Event;

use IdeasBucket\QueueBundle\Job\JobsInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class JobProcessing
 *
 * @package IdeasBucket\QueueBundle\Event
 */
class JobProcessing extends Event
{
    /**
     * The connection name.
     *
     * @var string
     */
    private $connectionName;
    /**
     * The job instance.
     *
     * @var JobsInterface
     */
    private $job;

    /**
     * JobProcessing constructor.
     *
     * @param string        $connectionName
     * @param JobsInterface $job
     */
    public function __construct($connectionName, JobsInterface $job)
    {
        $this->connectionName = $connectionName;
        $this->job = $job;
    }

    /**
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * @return JobsInterface
     */
    public function getJob()
    {
        return $this->job;
    }
}
