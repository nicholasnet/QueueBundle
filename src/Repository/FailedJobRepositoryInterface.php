<?php

namespace IdeasBucket\QueueBundle\Repository;

use IdeasBucket\QueueBundle\Entity\FailedJobEntityInterface;

/**
 * Interface FailedJobLoggerInterface
 *
 * @package IdeasBucket\QueueBundle\Utils
 */
interface FailedJobRepositoryInterface
{
    /**
     * Logs the failed message
     *
     * @param string     $connectionName Name of the connection
     * @param string     $queue          Name of the queue
     * @param string     $rawBody        Payload of the job
     * @param \Exception $exception      Exception that was thrown
     */
    public function log($connectionName, $queue, $rawBody, \Exception $exception);

    /**
     * Find all failed jobs
     *
     * @return FailedJobEntityInterface[]
     */
    public function findAll();

    /**
     * Find failed jobs by the id
     *
     * @param array $ids
     *
     * @return FailedJobEntityInterface[]
     */
    public function findByIds(array $ids);

    /**
     * @param FailedJobEntityInterface $failedJob
     */
    public function forget(FailedJobEntityInterface $failedJob);

    /**
     * Deletes the failed jobs
     *
     * @return int
     */
    public function flush();
}
