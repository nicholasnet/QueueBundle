<?php

namespace IdeasBucket\QueueBundle\Event;

use IdeasBucket\QueueBundle\Job\JobsInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class JobProcessed
 *
 * @package IdeasBucket\QueueBundle\Events
 */
class JobProcessed extends Event
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
     * Create a new event instance.
     *
     * @param string        $connectionName
     * @param JobsInterface $job
     */
    public function __construct($connectionName, $job)
    {
        $this->job = $job;
        $this->connectionName = $connectionName;
    }
}
