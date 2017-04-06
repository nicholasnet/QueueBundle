<?php

namespace IdeasBucket\QueueBundle\Type;

use IdeasBucket\Common\Utils\ArrayHelper;
use IdeasBucket\Common\Utils\StringHelper;
use IdeasBucket\QueueBundle\Job\JobsInterface;
use IdeasBucket\QueueBundle\Job\RedisJob;
use IdeasBucket\QueueBundle\Util\LuaScripts;
use Predis\Client;

/**
 * Class RedisQueue
 *
 * @package IdeasBucket\QueueBundle\Types
 */
class RedisQueue extends AbstractQueue implements QueueInterface
{
    /**
     * The Redis factory implementation.
     *
     * @var Client
     */
    protected $redis;

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
     * Create a new Redis queue instance.
     *
     * @param Client $redis
     * @param string $default
     * @param int    $retryAfter
     */
    public function __construct(Client $redis, $default = 'default', $retryAfter = 60)
    {
        $this->redis = $redis;
        $this->default = $default;
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
        $queue = $this->getQueue($queue);

        return $this->redis->eval(LuaScripts::size(), 3, $queue, $queue . ':delayed', $queue . ':reserved');
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  object|string $job
     * @param  mixed         $data
     * @param  string        $queue
     *
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
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
        $this->redis->rpush($this->getQueue($queue), $payload);

        return ArrayHelper::get(json_decode($payload, true), 'id');
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int $delay
     * @param  object|string $job
     * @param  mixed         $data
     * @param  string        $queue
     *
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->laterRaw($delay, $this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw job onto the queue after a delay.
     *
     * @param  \DateTime|int $delay
     * @param  string        $payload
     * @param  string        $queue
     *
     * @return mixed
     */
    protected function laterRaw($delay, $payload, $queue = null)
    {
        $this->redis->zadd(
            $this->getQueue($queue) . ':delayed', $this->availableAt($delay), $payload
        );

        return ArrayHelper::get(json_decode($payload, true), 'id');
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string $job
     * @param  mixed  $data
     *
     * @return string
     */
    protected function createPayloadArray($job, $data = '')
    {
        return array_merge(parent::createPayloadArray($job, $data), [
            'id'       => $this->getRandomId(),
            'attempts' => 0,
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
        $this->migrate($prefixed = $this->getQueue($queue));
        list($job, $reserved) = $this->retrieveNextJob($prefixed);

        if ($reserved) {

            return new RedisJob(
                $this->container,
                $this,
                $job,
                $reserved,
                $queue ?: $this->default
            );
        }
    }

    /**
     * Migrate any delayed or expired jobs onto the primary queue.
     *
     * @param  string $queue
     *
     * @return void
     */
    protected function migrate($queue)
    {
        $this->migrateExpiredJobs($queue . ':delayed', $queue);

        if (!is_null($this->retryAfter)) {

            $this->migrateExpiredJobs($queue . ':reserved', $queue);
        }
    }

    /**
     * Migrate the delayed jobs that are ready to the regular queue.
     *
     * @param  string $from
     * @param  string $to
     *
     * @return array
     */
    public function migrateExpiredJobs($from, $to)
    {
        return $this->redis->eval(LuaScripts::migrateExpiredJobs(), 2, $from, $to, $this->currentTime());
    }

    /**
     * Retrieve the next job from the queue.
     *
     * @param  string $queue
     *
     * @return array
     */
    protected function retrieveNextJob($queue)
    {
        return $this->redis->eval(LuaScripts::pop(), 2, $queue, $queue . ':reserved',
            $this->availableAt($this->retryAfter)
        );
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param  string   $queue
     * @param  RedisJob $job
     *
     * @return void
     */
    public function deleteReserved($queue, $job)
    {
        $this->redis->zrem($this->getQueue($queue) . ':reserved', $job->getReservedJob());
    }

    /**
     * Delete a reserved job from the reserved queue and release it.
     *
     * @param  string   $queue
     * @param  RedisJob $job
     * @param  int      $delay
     *
     * @return void
     */
    public function deleteAndRelease($queue, $job, $delay)
    {
        $queue = $this->getQueue($queue);
        $this->redis->eval(
            LuaScripts::release(), 2, $queue . ':delayed', $queue . ':reserved',
            $job->getReservedJob(), $this->availableAt($delay)
        );
    }

    /**
     * Get a random ID string.
     *
     * @return string
     */
    protected function getRandomId()
    {
        return StringHelper::random(32);
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null $queue
     *
     * @return string
     */
    public function getQueue($queue)
    {
        return 'queues:' . ($queue ?: $this->default);
    }

    /**
     * Get the underlying Redis instance.
     *
     * @return Client
     */
    public function getRedis()
    {
        return $this->redis;
    }
}
