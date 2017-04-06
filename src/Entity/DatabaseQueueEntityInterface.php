<?php

namespace IdeasBucket\QueueBundle\Entity;

/**
 * Interface DatabaseQueueEntityInterface
 *
 * @package IdeasBucket\QueueBundle\Entity
 */
interface DatabaseQueueEntityInterface
{
    /**
     * @return mixed
     */
    public function getId();

    /**
     * @return string
     */
    public function getQueue();

    /**
     * @param string $queue
     *
     * @return DatabaseQueueEntityInterface
     */
    public function setQueue($queue);

    /**
     * @return array
     */
    public function getPayload();

    /**
     * @param array $payload
     *
     * @return DatabaseQueueEntityInterface
     */
    public function setPayload($payload);

    /**
     * @return int
     */
    public function getAttempts();

    /**
     * @param int $attempts
     *
     * @return DatabaseQueueEntityInterface
     */
    public function setAttempts($attempts);

    /**
     * @return int
     */
    public function getReservedAt();

    /**
     * @param int $reservedAt
     *
     * @return DatabaseQueueEntityInterface
     */
    public function setReservedAt($reservedAt);

    /**
     * @return int
     */
    public function getAvailableAt();

    /**
     * @param int $availableAt
     *
     * @return DatabaseQueueEntityInterface
     */
    public function setAvailableAt($availableAt);

    /**
     * @return int
     */
    public function getCreatedAt();

    /**
     * @param int $createdAt
     *
     * @return DatabaseQueueEntityInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * @return int
     */
    public function touch();

    /**
     * @return int
     */
    public function increment();
}