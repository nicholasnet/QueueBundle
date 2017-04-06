<?php

namespace IdeasBucket\QueueBundle\Type;

use IdeasBucket\Common\Utils\Encrypter;
use IdeasBucket\QueueBundle\Exception\InvalidArgumentException;
use IdeasBucket\QueueBundle\Exception\InvalidPayloadException;
use IdeasBucket\QueueBundle\QueueableInterface;
use IdeasBucket\QueueBundle\Util\InteractsWithTimeTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AbstractQueue
 *
 * @package IdeasBucket\QueueBundle\Types
 */
abstract class AbstractQueue
{
    use InteractsWithTimeTrait;

    /**
     * The IoC container instance.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * The encrypter implementation.
     *
     * @var Encrypter
     */
    protected $encrypter;

    /**
     * The connection name for the queue.
     *
     * @var string
     */
    protected $connectionName;

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     *
     * @return mixed
     */
    abstract public function push($job, $data = '', $queue = null);

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $queue
     * @param  string  $job
     * @param  mixed   $data
     *
     * @return mixed
     */
    public function pushOn($queue, $job, $data = '')
    {
        return $this->push($job, $data, $queue);
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
    abstract public function later($delay, $job, $data = '', $queue = null);

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  string  $queue
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     *
     * @return mixed
     */
    public function laterOn($queue, $delay, $job, $data = '')
    {
        return $this->later($delay, $job, $data, $queue);
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array   $jobs
     * @param  mixed   $data
     * @param  string  $queue
     *
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ($jobs as $job) {

            $this->push($job, $data, $queue);
        }
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
        $payload = json_encode($this->createPayloadArray((string) $job, $data));

        if (JSON_ERROR_NONE !== json_last_error()) {

            throw new InvalidPayloadException;
        }

        return $payload;
    }

    /**
     * Create a payload array from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     *
     * @return array
     */
    protected function createPayloadArray($job, $data = '')
    {
        return $this->createStringPayload($job, $data);
    }

    /**
     * Create a typical, string based queue payload array.
     *
     * @param  string  $job
     * @param  mixed  $data
     *
     * @return array
     */
    protected function createStringPayload($job, $data)
    {
        if ($this->container->has($job) === false) {

            throw new InvalidArgumentException('This job #' . $job. 'is not a service.');
        }

        $resolvedJob = $this->container->get($job);

        if (($resolvedJob instanceof QueueableInterface) === false) {

            throw new InvalidArgumentException('All Job must implement ' . QueueableInterface::class);
        }

        $payload = ['job' => $job, 'data' => $data];

        if (isset($resolvedJob->maxTries)) {

            $payload['maxTries'] = $resolvedJob->maxTries;

        } elseif (method_exists($resolvedJob, 'getMaxTries')) {

            $payload['maxTries'] = $resolvedJob->getMaxTries();

        } else {

            $payload['maxTries'] = null;
        }

        if (isset($resolvedJob->timeout)) {

            $payload['timeout'] = $resolvedJob->timeout;

        } elseif (method_exists($resolvedJob, 'getTimeout')) {

            $payload['timeout'] = $resolvedJob->getTimeout();

        } else {

            $payload['timeout'] = null;
        }

        return $payload;
    }

    /**
     * Get the connection name for the queue.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Set the connection name for the queue.
     *
     * @param  string  $name
     *
     * @return $this
     */
    public function setConnectionName($name)
    {
        $this->connectionName = $name;

        return $this;
    }

    /**
     * Set the IoC container instance.
     *
     * @param  ContainerInterface  $container
     *
     * @return static
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }
}
