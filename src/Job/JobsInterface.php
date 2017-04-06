<?php

namespace IdeasBucket\QueueBundle\Job;

/**
 * Interface JobsInterface
 *
 * @package IdeasBucket\QueueBundle\Jobs
 */
interface JobsInterface
{
    /**
     * Fire the job.
     */
    public function fire();

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     */
    public function release($delay = 0);

    /**
     * Delete the job from the queue.
     */
    public function delete();

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted();

    /**
     * Determine if the job has been deleted or released.
     *
     * @return bool
     */
    public function isDeletedOrReleased();

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts();

    /**
     * Process an exception that caused the job to fail.
     *
     * @param  \Throwable  $e
     */
    public function failed($e);

    /**
     * The number of times to attempt a job.
     *
     * @return int|null
     */
    public function maxTries();

    /**
     * The number of seconds the job can run.
     *
     * @return int|null
     */
    public function timeout();

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName();

    /**
     * Mark the job as "failed".
     */
    public function markAsFailed();

    /**
     * Get the name of the connection the job belongs to.
     *
     * @return string
     */
    public function getConnectionName();

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue();

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody();

    /**
     * Determine if the job was released back into the queue.
     *
     * @return bool
     */
    public function isReleased();

    /**
     * Determine if the job has been marked as a failure.
     *
     * @return bool
     */
    public function hasFailed();

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload();
}