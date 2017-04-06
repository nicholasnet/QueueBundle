<?php

namespace IdeasBucket\QueueBundle\Job;

use IdeasBucket\QueueBundle\Entity\DatabaseQueueEntityInterface;
use IdeasBucket\QueueBundle\Type\DatabaseQueue;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DatabaseJob
 *
 * @package IdeasBucket\QueueBundle\Jobs
 */
class DatabaseJob extends AbstractJob implements JobsInterface
{
    /**
     * The database queue instance.
     *
     * @var DatabaseQueue
     */
    protected $database;

    /**
     * The database job payload.
     *
     * @var DatabaseQueueEntityInterface
     */
    protected $job;

    /**
     * Create a new job instance.
     *
     * @param ContainerInterface           $container
     * @param DatabaseQueue                $database
     * @param DatabaseQueueEntityInterface $job
     * @param                              $connectionName
     * @param                              $queue
     */
    public function __construct(ContainerInterface $container, DatabaseQueue $database, DatabaseQueueEntityInterface $job, $connectionName, $queue)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->database = $database;
        $this->container = $container;
        $this->connectionName = $connectionName;
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int $delay
     *
     * @return mixed
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $this->delete();

        return $this->database->release($this->queue, $this->job, $delay);
    }

    /**
     * Delete the job from the queue.
     */
    public function delete()
    {
        parent::delete();

        $this->database->deleteReserved($this->queue, $this->job->getId());
    }

    /**
     * @inheritdoc
     */
    public function payload()
    {
        return $this->getRawBody();
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return (int) $this->job->getAttempts();
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->job->getId();
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->job->getPayload();
    }
}