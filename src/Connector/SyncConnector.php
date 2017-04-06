<?php

namespace IdeasBucket\QueueBundle\Connector;

use IdeasBucket\QueueBundle\Type\SyncQueue;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class SyncConnectors
 *
 * @package IdeasBucket\QueueBundle\Connectors
 */
class SyncConnector implements ConnectorInterface
{
    /**
     * @inheritDoc
     */
    public function connect(ContainerInterface $container, array $config)
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $container->get('event_dispatcher');

        return (new SyncQueue($eventDispatcher))->setContainer($container);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'sync';
    }
}