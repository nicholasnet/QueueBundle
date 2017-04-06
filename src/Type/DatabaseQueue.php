<?php

namespace IdeasBucket\QueueBundle\Type;

use IdeasBucket\Common\Utils\Collection;
use IdeasBucket\QueueBundle\Entity\DatabaseQueueEntityInterface;
use IdeasBucket\QueueBundle\Job\DatabaseJob;
use IdeasBucket\QueueBundle\Job\JobsInterface;
use IdeasBucket\QueueBundle\Repository\DatabaseQueueRepositoryInterface;
use IdeasBucket\QueueBundle\Exception\InvalidPayloadException;

/**
 * Class DatabaseQueue
 *
 * @package IdeasBucket\QueueBundle\Type
 */
class DatabaseQueue extends AbstractQueue implements QueueInterface
{
    /**
     * The database connection instance.
     *
     * @var DatabaseQueueRepositoryInterface
     */
    protected $database;

    /**
     * The name of the default queue.
     *
     * @var string
     */
    protected $default;

    /**
     * The expiration time of a job.
     *
     * @var int|null
     */
    protected $retryAfter = 60;

    /**
     * Create a new database queue instance.
     *
     * @param  DatabaseQueueRepositoryInterface $database
     * @param  string                           $default
     * @param  int                              $retryAfter
     */
    public function __construct(DatabaseQueueRepositoryInterface $database, $default = 'default', $retryAfter = 60)
    {
        $this->default = $default;
        $this->database = $database;
        $this->retryAfter = $retryAfter;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string $queue
     *
     * @return int
     */
    public function size($queue = null)
    {
        return $this->database->getCount($this->getQueue($queue));
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string $job
     * @param  mixed  $data
     * @param  string $queue
     *
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushToDatabase($queue, $this->createPayload($job, $data));
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string $payload
     * @param  string $queue
     * @param  array  $options
     *
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->pushToDatabase($queue, $payload);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int $delay
     * @param  string        $job
     * @param  mixed         $data
     * @param  string        $queue
     *
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->pushToDatabase($queue, $this->createPayload($job, $data), $delay);
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  string $queue
     *
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        $queue = $this->getQueue($queue);

        $availableAt = $this->availableAt();

        return $this->database->saveInBulk(Collection::make((array)$jobs)->map(

            function ($job) use ($queue, $data, $availableAt) {

                return $this->buildDatabaseRecord($queue, $this->createPayload($job, $data), $availableAt);
            }

        )->all());
    }

    /**
     * Release a reserved job back onto the queue.
     *
     * @param  string                       $queue
     * @param  DatabaseQueueEntityInterface $job
     * @param  int                          $delay
     *
     * @return mixed
     */
    public function release($queue, $job, $delay)
    {
        return $this->pushToDatabase($queue, $job->getPayload(), $delay, $job->getAttempts());
    }

    /**
     * Push a raw payload to the database with a given delay.
     *
     * @param  string|null   $queue
     * @param  string        $payload
     * @param  \DateTime|int $delay
     * @param  int           $attempts
     *
     * @return mixed
     */
    protected function pushToDatabase($queue, $payload, $delay = 0, $attempts = 0)
    {
        $record = $this->buildDatabaseRecord(
            $this->getQueue($queue), $payload, $this->availableAt($delay), $attempts
        );

        $this->database->save($record);

        return $record->getId();
    }

    /**
     * Create an array to insert for the given job.
     *
     * @param  string|null $queue
     * @param  string      $payload
     * @param  int         $availableAt
     * @param  int         $attempts
     *
     * @return DatabaseQueueEntityInterface
     */
    protected function buildDatabaseRecord($queue, $payload, $availableAt, $attempts = 0)
    {
        return $this->database->createRecord([
            'queue'        => $queue,
            'payload'      => $payload,
            'attempts'     => $attempts,
            'reserved_at'  => null,
            'available_at' => $availableAt,
            'created_at'   => $this->currentTime(),
        ]);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string $queue
     *
     * @return JobsInterface|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        if ($job = $this->getNextAvailableJob($queue)) {

            return $this->marshalJob($queue, $job);
        }
    }

    /**
     * Get the next available job for the queue.
     *
     * @param  string|null $queue
     *
     * @return DatabaseQueueEntityInterface|null
     */
    protected function getNextAvailableJob($queue)
    {
        return $this->database->getNextAvailableJob($this->getQueue($queue), $this->retryAfter);
    }

    /**
     * Marshal the reserved job into a DatabaseJob instance.
     *
     * @param  string                       $queue
     * @param  DatabaseQueueEntityInterface $job
     *
     * @return DatabaseJob
     */
    protected function marshalJob($queue, DatabaseQueueEntityInterface $job)
    {
        $job = $this->markJobAsReserved($job);

        return new DatabaseJob($this->container, $this, $job, $this->connectionName, $queue);
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     *
     * @return string
     *
     * @throws InvalidPayloadException
     */
    protected function createPayload($job, $data = '')
    {
        $payload = $this->createPayloadArray((string) $job, $data);

        if (JSON_ERROR_NONE !== json_last_error()) {

            throw new InvalidPayloadException;
        }

        return $payload;
    }

    /**
     * Mark the given job ID as reserved.
     *
     * @param  DatabaseQueueEntityInterface $job
     *
     * @return DatabaseQueueEntityInterface
     */
    protected function markJobAsReserved(DatabaseQueueEntityInterface $job)
    {
        $job->touch();
        $job->increment();

        $this->database->save($job);

        return $job;
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param  string $queue
     * @param  string $id
     */
    public function deleteReserved($queue, $id)
    {
        $job = $this->database->findById($id);
        $this->database->delete($job);
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null $queue
     *
     * @return string
     */
    protected function getQueue($queue)
    {
        return $queue ?: $this->default;
    }

    /**
     * Get the underlying database instance.
     *
     * @return DatabaseQueueRepositoryInterface
     */
    public function getDatabase()
    {
        return $this->database;
    }
}
