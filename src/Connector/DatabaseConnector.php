<?php

namespace IdeasBucket\QueueBundle\Connector;

use IdeasBucket\Common\Utils\ArrayHelper;
use IdeasBucket\QueueBundle\Repository\DatabaseQueueRepositoryInterface;
use IdeasBucket\QueueBundle\Type\DatabaseQueue;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DatabaseConnector
 *
 * @package IdeasBucket\QueueBundle\Connectors
 */
class DatabaseConnector implements ConnectorInterface
{
    /**
     * Database connections.
     *
     * @var DatabaseQueueRepositoryInterface
     */
    protected $database;

    /**
     * Create a new connector instance.
     *
     * @param  DatabaseQueueRepositoryInterface  $database
     */
    public function __construct(DatabaseQueueRepositoryInterface $database = null)
    {
        $this->database = $database;
    }

    /**
     * @inheritDoc
     */
    public function connect(ContainerInterface $container, array $config)
    {
        if ($this->database === null) {

            throw new InvalidConfigurationException('In order to use database driver for the queue you must configure repository.');
        }

        return (new DatabaseQueue(
            $this->database,
            $config['queue'],
            ArrayHelper::get($config, 'retry_after', 60)
        ))->setContainer($container);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'database';
    }
}
