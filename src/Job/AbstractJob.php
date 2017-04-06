<?php

namespace IdeasBucket\QueueBundle\Job;

use IdeasBucket\Common\Utils\ArrayHelper;
use IdeasBucket\QueueBundle\QueueableInterface;
use IdeasBucket\QueueBundle\QueueErrorInterface;
use IdeasBucket\QueueBundle\Util\InteractsWithTimeTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AbstractJob
 *
 * @package IdeasBucket\QueueBundle\Jobs
 */
abstract class AbstractJob
{
    use InteractsWithTimeTrait;

    /**
     * The job handler instance.
     *
     * @var mixed
     */
    protected $instance;

    /**
     * The IoC container instance.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Indicates if the job has been deleted.
     *
     * @var bool
     */
    protected $deleted = false;

    /**
     * Indicates if the job has been released.
     *
     * @var bool
     */
    protected $released = false;

    /**
     * Indicates if the job has failed.
     *
     * @var bool
     */
    protected $failed = false;

    /**
     * The name of the connection the job belongs to.
     */
    protected $connectionName;

    /**
     * The name of the queue the job belongs to.
     *
     * @var string
     */
    protected $queue;

    /**
     * Fire the job.
     */
    public function fire()
    {
        $payload = $this->payload();
        $this->resolve($payload['job'])->fire($this, $payload['data']);
    }

    /**
     * @inheritdoc
     */
    public function payload()
    {
        return json_decode($this->getRawBody(), true);
    }

    /**
     * @return mixed
     */
    abstract public function getRawBody();

    /**
     * Resolve the given class.
     *
     * @param  string $class
     *
     * @throws InvalidJobException
     *
     * @return object
     */
    protected function resolve($class)
    {
        if ($this->container->has($class)) {

            $job = $this->container->get($class);

            if ($job instanceof QueueableInterface) {

                return $job;
            }

            throw new InvalidJobException('Job service must implement ' . QueueableInterface::class);
        }

        throw new InvalidJobException('Service Id ' . $class . ' not found.');
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        $this->deleted = true;
    }

    /**
     * @inheritdoc
     */
    public function release($delay = 0)
    {
        $this->released = true;
    }

    /**
     * @inheritdoc
     */
    public function isDeletedOrReleased()
    {
        return $this->isDeleted() || $this->isReleased();
    }

    /**
     * @inheritdoc
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @inheritdoc
     */
    public function isReleased()
    {
        return $this->released;
    }

    /**
     * @inheritdoc
     */
    public function hasFailed()
    {
        return $this->failed;
    }

    /**
     * @inheritdoc
     */
    public function failed($e)
    {
        $this->markAsFailed();
        $payload = $this->payload();
        $jobClass = $this->resolve($payload['job']);

        if ($jobClass instanceof QueueErrorInterface) {

            $jobClass->failed($e, $payload['data']);
        }
    }

    /**
     * @inheritdoc
     */
    public function markAsFailed()
    {
        $this->failed = true;
    }

    /**
     * @inheritdoc
     */
    public function maxTries()
    {
        return ArrayHelper::get($this->payload(), 'maxTries');
    }

    /**
     * @inheritdoc
     */
    public function timeout()
    {
        return ArrayHelper::get($this->payload(), 'timeout');
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->payload()['job'];
    }

    /**
     * @inheritdoc
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * @inheritdoc
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Get the service container instance.
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get the resolved name of the queued job class.
     *
     * Resolves the name of "wrapped" jobs such as class-based handlers.
     *
     * @return string
     */
    public function resolveName()
    {
        if (! empty($payload['displayName'])) {

            return $payload['displayName'];
        }

        return $this->getName();
    }
}
