<?php

namespace IdeasBucket\QueueBundle\Type;


use IdeasBucket\QueueBundle\Event\EventsList;
use IdeasBucket\QueueBundle\Event\JobExceptionOccurred;
use IdeasBucket\QueueBundle\Event\JobFailed;
use IdeasBucket\QueueBundle\Event\JobProcessed;
use IdeasBucket\QueueBundle\Event\JobProcessing;
use IdeasBucket\QueueBundle\Exception\ManuallyFailedException;
use IdeasBucket\QueueBundle\Job\JobsInterface;
use IdeasBucket\QueueBundle\Job\SyncJob;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class SyncQueue
 *
 * @package IdeasBucket\QueueBundle\Type
 */
class SyncQueue extends AbstractQueue implements QueueInterface
{
    /**
     * The event dispatcher instance.
     *
     * @var EventDispatcherInterface
     */
    protected $events;

    /**
     * SyncQueue constructor.
     *
     * @param EventDispatcherInterface $events
     */
    public function __construct(EventDispatcherInterface $events)
    {
        $this->events = $events;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return 0;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     *
     * @throws \Exception|\Throwable
     */
    public function push($job, $data = '', $queue = null)
    {
        $queueJob = $this->resolveJob($this->createPayload($job, $data), $queue);

        try {

            $this->raiseBeforeJobEvent($queueJob);
            $queueJob->fire();
            $this->raiseAfterJobEvent($queueJob);

        } catch (\Exception $e) {

            $this->handleException($queueJob, $e);

        } catch (\Throwable $e) {

            $this->handleException($queueJob, new FatalThrowableError($e));
        }

        return 0;
    }

    /**
     * Resolve a Sync job instance.
     *
     * @param  string  $payload
     * @param  string  $queue
     *
     * @return SyncJob
     */
    protected function resolveJob($payload, $queue)
    {
        return new SyncJob($this->container, $payload, $this->connectionName, $queue);
    }

    /**
     * Raise the before queue job event.
     *
     * @param  JobsInterface  $job
     */
    protected function raiseBeforeJobEvent(JobsInterface $job)
    {
        $this->events->dispatch(EventsList::JOB_PROCESSING, new JobProcessing($this->connectionName, $job));
    }

    /**
     * Raise the after queue job event.
     *
     * @param  JobsInterface  $job
     */
    protected function raiseAfterJobEvent(JobsInterface $job)
    {
        $this->events->dispatch(EventsList::JOB_PROCESSED, new JobProcessed($this->connectionName, $job));
    }
    /**
     * Raise the exception occurred queue job event.
     *
     * @param  JobsInterface  $job
     * @param  \Exception  $e
     */
    protected function raiseExceptionOccurredJobEvent(JobsInterface $job, $e)
    {
        $this->events->dispatch(EventsList::JOB_EXCEPTION_OCCURRED, new JobExceptionOccurred($this->connectionName, $job, $e));
    }

    /**
     * Handle an exception that occurred while processing a job.
     *
     * @param  JobsInterface  $queueJob
     * @param  \Exception  $e
     *
     * @throws \Exception
     */
    protected function handleException($queueJob, $e)
    {
        $this->raiseExceptionOccurredJobEvent($queueJob, $e);
        $queueJob->markAsFailed();

        if ($queueJob->isDeleted()) {

            return;
        }

        try {
            // If the job has failed, we will delete it, call the "failed" method and then call
            // an event indicating the job has failed so it can be logged if needed. This is
            // to allow every developer to better keep monitor of their failed queue jobs.
            $queueJob->delete();
            $queueJob->failed($e);

        } finally {

            $this->events->dispatch(EventsList::JOB_FAILED, new JobFailed($this->connectionName, $queueJob, $e ?: new ManuallyFailedException));
        }

        throw $e;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     *
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        //
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     *
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     *
     * @return JobsInterface|null
     */
    public function pop($queue = null)
    {
        //
    }
}