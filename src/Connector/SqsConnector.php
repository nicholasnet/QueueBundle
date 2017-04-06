<?php

namespace IdeasBucket\QueueBundle\Connector;

use IdeasBucket\Common\Utils\ArrayHelper;
use IdeasBucket\QueueBundle\Type\SqsQueue;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Aws\Sqs\SqsClient;

/**
 * Class SqsConnector
 *
 * @package IdeasBucket\QueueBundle\Connector
 */
class SqsConnector implements ConnectorInterface
{
    /**
     * @inheritDoc
     */
    public function connect(ContainerInterface $container, array $config)
    {
        $config = $this->getDefaultConfiguration($config);

        if ($config['key'] && $config['secret']) {

            $config['credentials'] = ArrayHelper::only($config, ['key', 'secret']);
        }

        return (new SqsQueue(
            new SqsClient($config), $config['queue'], ArrayHelper::get($config, 'prefix', '')
        ))->setContainer($container);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'sqs';
    }

    /**
     * Get the default configuration for SQS.
     *
     * @param  array  $config
     * @return array
     */
    protected function getDefaultConfiguration(array $config)
    {
        return array_merge(['version' => 'latest', 'http' => ['timeout' => 60, 'connect_timeout' => 60]], $config);
    }
}