<?php

namespace IdeasBucket\QueueBundle\Entity;

/**
 * Interface FailedJobEntity
 *
 * @package IdeasBucket\QueueBundle\Entity
 */
interface FailedJobEntityInterface
{
    /**
     * @return mixed
     */
    public function getId();

    /**
     * @param $payload
     *
     * @return FailedJobEntityInterface
     */
    public function setPayload($payload);

    /**
     * @return mixed
     */
    public function getConnection();

    /**
     * @return mixed
     */
    public function getPayload();

    /**
     * @return string
     */
    public function getQueue();

    /**
     * @return int
     */
    public function getException();

    /**
     * @param int $exception
     *
     * @return FailedJobEntityInterface
     */
    public function setException($exception);

    /**
     * @return \DateTime
     */
    public function getFailedAt();

    /**
     * @param string $connection
     *
     * @return FailedJobEntityInterface
     */
    public function setConnection($connection);

    /**
     * @param string $queue
     *
     * @return FailedJobEntityInterface
     */
    public function setQueue($queue);

    /**
     * @param \DateTime $failedAt
     *
     * @return FailedJobEntityInterface
     */
    public function setFailedAt(\DateTime $failedAt);
}