<?php

namespace IdeasBucket\QueueBundle\Job;

use Pheanstalk\Pheanstalk;
use Pheanstalk\Job as PheanstalkJob;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BeanstalkdJob
 *
 * @package IdeasBucket\QueueBundle\Jobs
 */
class BeanstalkdJob extends AbstractJob implements JobsInterface
{
    /**
     * The Pheanstalk instance.
     *
     * @var Pheanstalk
     */
    protected $pheanstalk;

    /**
     * The Pheanstalk job instance.
     *
     * @var PheanstalkJob
     */
    protected $job;

    /**
     * BeanstalkdJob constructor.
     *
     * @param ContainerInterface $container
     * @param Pheanstalk         $pheanstalk
     * @param                    $job
     * @param                    $connectionName
     * @param                    $queue
     */
    public function __construct(ContainerInterface $container, Pheanstalk $pheanstalk, $job, $connectionName, $queue)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->container = $container;
        $this->pheanstalk = $pheanstalk;
        $this->connectionName = $connectionName;
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $priority = Pheanstalk::DEFAULT_PRIORITY;
        $this->pheanstalk->release($this->job, $priority, $delay);
    }

    /**
     * Bury the job in the queue.
     *
     * @return void
     */
    public function bury()
    {
        parent::release();

        $this->pheanstalk->bury($this->job);
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $this->pheanstalk->delete($this->job);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        $stats = $this->pheanstalk->statsJob($this->job);

        return (int) $stats->reserves;
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
        return $this->job->getData();
    }

    /**
     * Get the underlying Pheanstalk instance.
     *
     * @return Pheanstalk
     */
    public function getPheanstalk()
    {
        return $this->pheanstalk;
    }

    /**
     * Get the underlying Pheanstalk job.
     *
     * @return PheanstalkJob
     */
    public function getPheanstalkJob()
    {
        return $this->job;
    }
}