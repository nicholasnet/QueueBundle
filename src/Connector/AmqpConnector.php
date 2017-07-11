<?php

namespace IdeasBucket\QueueBundle\Connector;

use IdeasBucket\QueueBundle\Type\AmqpQueue;
use IdeasBucket\QueueBundle\Type\QueueInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AmqpConnector
 *
 * @package IdeasBucket\QueueBundle\Connector
 */
class AmqpConnector implements ConnectorInterface
{
    /**
     * @var \AMQPConnection
     */
    private $connection = false;

    /**
     * @inheritDoc
     */
    public function connect(ContainerInterface $container, array $config)
    {
        if (false === $this->connection) {

            $config = $this->processConfig($config);
            $config['login'] = $config['user'];
            $config['password'] = $config['pass'];
            $this->connection = new \AMQPConnection($config);
            $config['persisted'] ? $this->connection->pconnect() : $this->connection->connect();
        }

        if (false == $this->connection->isConnected()) {

            $config['persisted'] ? $this->connection->preconnect() : $this->connection->reconnect();
        }

        return (new AmqpQueue($this->connection, $config['queue']))->setContainer($container);
    }

    /**
     * @param array $config
     *
     * @return array
     */
    private function processConfig(array $config)
    {
        if (isset($config['dsn'])) {

            $config = $this->parseDsn($config['dsn']);
        }

        $supportedMethods = ['basic_get', 'basic_consume'];

        if (false == in_array($config['receive_method'], $supportedMethods, true)) {

            throw new \LogicException(sprintf(
                'Invalid "receive_method" option value "%s". It could be only "%s"',
                $config['receive_method'],
                implode('", "', $supportedMethods)
            ));
        }

        if ('basic_consume' == $config['receive_method']) {

            if (false == (version_compare(phpversion('amqp'), '1.9.1', '>=') || phpversion('amqp') == '1.9.1-dev')) {

                throw new \LogicException('The "basic_consume" method does not work on amqp extension prior 1.9.1 version.');
            }
        }

        return $config;
    }

    /**
     * @param string $dsn
     *
     * @return array
     */
    private function parseDsn($dsn)
    {
        $dsnConfig = parse_url($dsn);

        if (false === $dsnConfig) {

            throw new \LogicException(sprintf('Failed to parse DSN "%s"', $dsn));
        }

        $dsnConfig = array_replace([
            'scheme' => null,
            'host' => null,
            'port' => null,
            'user' => null,
            'pass' => null,
            'path' => null,
            'query' => null,
        ], $dsnConfig);

        if ('amqp' !== $dsnConfig['scheme']) {

            throw new \LogicException(sprintf('The given DSN scheme "%s" is not supported. Could be "amqp" only.', $dsnConfig['scheme']));
        }

        if ($dsnConfig['query']) {

            $query = [];
            parse_str($dsnConfig['query'], $query);
            $dsnConfig = array_replace($query, $dsnConfig);
        }

        $dsnConfig['vhost'] = ltrim($dsnConfig['path'], '/');
        unset($dsnConfig['scheme'], $dsnConfig['query'], $dsnConfig['fragment'], $dsnConfig['path']);
        $config = array_map(function ($value) {

            return urldecode($value);

        }, $dsnConfig);

        return $config;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'amqp';
    }
}