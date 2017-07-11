<?php

namespace IdeasBucket\QueueBundle\Type;

use IdeasBucket\Common\Utils\ArrayHelper;
use IdeasBucket\QueueBundle\Job\JobsInterface;

/**
 * Class AmqpQueue
 *
 * @package IdeasBucket\QueueBundle\Type
 */
class AmqpQueue extends AbstractQueue implements QueueInterface
{
    /**
     * @var \AMQPConnection
     */
    private $connection;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @var \AMQPChannel
     */
    private $channel;

    /**
     * AmqpQueue constructor.
     *
     * @param \AMQPConnection $connection
     * @param array $config
     */
    public function __construct(\AMQPConnection $connection, array $config)
    {
        $this->connection = $connection;
        $this->configuration = $config;
    }

    /**
     * @param \AMQPConnection $extConnection
     *
     * @return \AMQPChannel
     */
    private function createContext(\AMQPConnection $extConnection)
    {
        if ($this->channel instanceof \AMQPChannel) {

            return $this->channel;
        }

        $this->channel = new \AMQPChannel($extConnection);

        if (empty($this->configuration['pre_fetch_count']) === false) {

            $this->channel->setPrefetchCount((int) $this->configuration['pre_fetch_count']);
        }

        if (empty($this->configuration['pre_fetch_size']) === false) {

            $this->channel->setPrefetchSize((int) $this->configuration['pre_fetch_size']);
        }

        return $this->channel;
    }

    /**
     * @inheritDoc
     */
    public function push($job, $data = '', $queue = null)
    {
        if (is_array($data)) {

            return $this->pushRaw($this->createPayload($job, $data), $queue, ArrayHelper::get($data, 'options'));
        }

        if (is_string($data)) {

            $decodedData = json_decode($data, true);

            if ($decodedData !== false) {

                return $this->pushRaw($this->createPayload($job, $data), $queue, ArrayHelper::get($decodedData, 'options'));
            }
        }

        if ($data instanceof \JsonSerializable) {

            $decodedData = $data->jsonSerialize();

            if (is_array($decodedData)) {

                return $this->pushRaw($this->createPayload($job, $data), $queue, ArrayHelper::get($decodedData, 'options'));
            }
        }

        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * @inheritDoc
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        // TODO: Implement later() method.
    }

    /**
     * @inheritDoc
     */
    public function size($queue = null)
    {
        // TODO: Implement size() method.
    }

    /**
     * @inheritDoc
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $amqpAttributes = [];
        $flags = AMQP_NOPARAM;
        $flags |= AMQP_DURABLE;

        if (isset($options['properties'])) {

            $amqpAttributes['headers'] = $options['properties'];
        }

        if (isset($options['flags'])) {

            $flags |= $options['flags'];
        }

        $amqpExchange = new \AMQPExchange($this->createContext($this->connection));
        $amqpExchange->setType(AMQP_EX_TYPE_DIRECT);
        $amqpExchange->setName('');
        $amqpExchange->publish($payload, $this->getQueue($queue), $flags, $amqpAttributes);
    }

    /**
     * @inheritDoc
     */
    public function pop($queue = null)
    {
        // TODO: Implement pop() method.
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
        return $queue ?: $this->configuration['queue'];
    }
}