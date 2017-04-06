<?php

namespace IdeasBucket\QueueBundle\Connector;

use IdeasBucket\Common\Utils\ArrayHelper;
use IdeasBucket\QueueBundle\Type\BeanstalkdQueue as Queue;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Connection;
use Pheanstalk\PheanstalkInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BeanstalkdConnector
 *
 * @package IdeasBucket\QueueBundle\Connector
 */
class BeanstalkdConnector implements ConnectorInterface
{
    /**
     * @inheritDoc
     */
    public function connect(ContainerInterface $container, array $config)
    {
        $retryAfter = ArrayHelper::get($config, 'retry_after', Pheanstalk::DEFAULT_TTR);

        return (new Queue($this->pheanstalk($config), $config['queue'], $retryAfter))->setContainer($container);
    }

    /**
     * Create a Pheanstalk instance.
     *
     * @param  array  $config
     *
     * @return \Pheanstalk\Pheanstalk
     */
    protected function pheanstalk(array $config)
    {
        $port = ArrayHelper::get($config, 'port', PheanstalkInterface::DEFAULT_PORT);
        $timeout = ArrayHelper::get($config, 'timeout', Connection::DEFAULT_CONNECT_TIMEOUT);
        $persistent = ArrayHelper::get($config, 'persistent', false);

        return new Pheanstalk($config['host'], $port, $timeout, $persistent);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'beanstalkd';
    }
}