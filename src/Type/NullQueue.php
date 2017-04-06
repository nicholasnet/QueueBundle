<?php

namespace IdeasBucket\QueueBundle\Type;

/**
 * Class NullQueue
 *
 * @package IdeasBucket\QueueBundle\Types
 */
class NullQueue extends AbstractQueue implements QueueInterface
{
    /**
     * @inheritDoc
     */
    public function size($queue = null)
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function push($job, $data = '', $queue = null)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function pop($queue = null)
    {
        //
    }
}