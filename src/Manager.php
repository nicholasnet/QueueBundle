<?php

namespace IdeasBucket\QueueBundle;

use IdeasBucket\Common\Utils\ArrayHelper;
use IdeasBucket\QueueBundle\Connector\ConnectorInterface;
use IdeasBucket\QueueBundle\Event\EventsList;
use IdeasBucket\QueueBundle\Type\QueueInterface;
use IdeasBucket\QueueBundle\Util\SwitchInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Manager
 *
 * @package IdeasBucket\QueueBundle
 */
class Manager implements ContainerAwareInterface
{
    /**
     * Lock name.
     *
     * @string
     */
    const LOCK_NAME = 'queue-worker.lock';

    /**
     * The collection of Queue configuration.
     *
     * @var array
     */
    private $configuration;

    /**
     * The collection of Queue connectors.
     *
     * @var array
     */
    private $connectors;

    /**
     * The collection of Queue connections.
     *
     * @var array
     */
    private $connections;

    /**
     * The application container implementation.
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Basic system implementation.
     *
     * @var SwitchInterface
     */
    private $switch;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Manager constructor.
     *
     * @param ContainerInterface       $container
     * @param array                    $configs
     * @param EventDispatcherInterface $dispatcher
     * @param SwitchInterface          $switch
     */
    public function __construct(ContainerInterface $container, array $configs, EventDispatcherInterface $dispatcher, SwitchInterface $switch)
    {
        $this->container = $container;
        $this->configuration = $configs;
        $this->dispatcher = $dispatcher;
        $this->switch = $switch;
    }

    /**
     * This method sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null.
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Register an event listener for the before job event.
     *
     * @param  mixed $callback
     *
     * @return void
     */
    public function before($callback)
    {
        $this->dispatcher->addListener(EventsList::JOB_PROCESSING, $callback);
    }

    /**
     * Register an event listener for the after job event.
     *
     * @param  mixed $callback
     *
     * @return void
     */
    public function after($callback)
    {
        $this->dispatcher->addListener(EventsList::JOB_PROCESSED, $callback);
    }

    /**
     * Register an event listener for the exception occurred job event.
     *
     * @param  mixed $callback
     *
     * @return void
     */
    public function exceptionOccurred($callback)
    {
        $this->dispatcher->addListener(EventsList::JOB_EXCEPTION_OCCURRED, $callback);
    }

    /**
     * Register an event listener for the daemon queue loop.
     *
     * @param  mixed $callback
     *
     * @return void
     */
    public function looping($callback)
    {
        $this->dispatcher->addListener(EventsList::LOOPING, $callback);
    }

    /**
     * Register an event listener for the failed job event.
     *
     * @param  mixed $callback
     *
     * @return void
     */
    public function failing($callback)
    {
        $this->dispatcher->addListener(EventsList::JOB_FAILED, $callback);
    }

    /**
     * Register an event listener for the daemon queue stopping.
     *
     * @param  mixed $callback
     *
     * @return void
     */
    public function stopping($callback)
    {
        $this->dispatcher->addListener(EventsList::WORKER_STOPPING, $callback);
    }

    /**
     * Determine if the driver is connected.
     *
     * @param  string $name
     *
     * @return bool
     */
    public function connected($name = null)
    {
        return isset($this->connections[ $name ?: $this->getDefaultDriver() ]);
    }

    /**
     * Resolve a queue connection instance.
     *
     * @param  string $name
     *
     * @return QueueInterface
     */
    public function connection($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        // If the connection has not been resolved yet we will resolve it now as all
        // of the connections are resolved when they are actually needed so we do
        // not make any unnecessary connection to the various queue end-points.
        if (!isset($this->connections[$name]) && isset($this->connectors[$name])) {

            return $this->resolve($name);

        } elseif (isset($this->connections[$name])) {

            return $this->connections[$name];

        } else {

            throw new \InvalidArgumentException('No connection driver found by this name ' . $name);
        }
    }

    /**
     * Resolve a queue connection.
     *
     * @param  string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return QueueInterface
     */
    protected function resolve($name)
    {
        $configuration = $this->getConfig($name);

        if (empty($configuration)) {

            throw new \InvalidArgumentException('You are trying to access connection ' . $name . ' but it is not configured.');
        }

        $this->connections[$name] = $this->connectors[$name]->connect($this->container, $configuration);

        return $this->connections[$name];
    }

    /**
     * Get the connector for a given driver.
     *
     * @param  string $driver
     *
     * @return ConnectorInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function getConnector($driver)
    {
        if (!isset($this->connectors[ $driver ])) {

            throw new \InvalidArgumentException("No connector for [$driver]");
        }

        return $this->connectors[ $driver ];
    }

    /**
     * @param ConnectorInterface $connector
     */
    public function addConnector(ConnectorInterface $connector)
    {
        $connectorName = $connector->getName();

        if (!isset($this->connectors[$connectorName])) {

            $this->connectors[$connectorName] = $connector;
        }
    }

    /**
     * Get the queue connection configuration.
     *
     * @param  string $name
     *
     * @return array
     */
    protected function getConfig($name)
    {
        if ($name === 'sync') {

            return ['driver' => 'sync'];
        }

        if (!is_null($name) && $name !== 'null') {

            //return $this->configuration['connections'][$name];
            return ArrayHelper::get($this->configuration, 'connections.' . $name);
        }

        return ['driver' => 'null'];
    }

    /**
     * Get the name of the default queue connection.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->configuration['default'];
    }

    /**
     * Get the full name for the given connection.
     *
     * @param  string $connection
     *
     * @return string
     */
    public function getName($connection = null)
    {
        return $connection ?: $this->getDefaultDriver();
    }

    /**
     * Determine if the application is in maintenance mode.
     *
     * @return bool
     */
    public function isDownForMaintenance()
    {
        return $this->switch->isOn(self::LOCK_NAME);
    }

    /**
     * This method pushes a new job onto the queue.
     *
     * @param  string $job        The job.
     * @param  mixed  $data       The data for the job.
     * @param  string $queue      The queue instance.
     * @param  string $connection The connection to use.
     *
     * @return mixed
     */
    public function push($job, $data = '', $queue = null, $connection = null)
    {
        return $this->connection($connection)->push($job, $data, $queue);
    }

    /**
     * This method pushes a jobs onto the queue.
     *
     * @param  array  $jobs       Jobs to push into the queue.
     * @param  mixed  $data       Data for the job.
     * @param  string $queue      Queue used by the job.
     * @param  string $connection Connection used by the queue.
     *
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null, $connection = null)
    {
        return $this->connection($connection)->bulk($jobs, $data, $queue);
    }

    /**
     * This method pushes a new job onto the queue after a delay.
     *
     * @param  \DateTime|int $delay      Delayed time after job which job needs to be pused into the queue.
     * @param  string        $job        Job to push.
     * @param  mixed         $data       Data for the job.
     * @param  string        $queue      Queue used by the job.
     * @param  string        $connection Connection used by the queue.
     *
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null, $connection = null)
    {
        return $this->connection($connection)->later($delay, $job, $data, $queue);
    }

    /**
     * Dynamically pass calls to the default connection.
     *
     * @param  string $method
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->{$method}(...$parameters);
    }
}
