<?php

namespace IdeasBucket\QueueBundle\Util;

/**
 * Class InteractsWithTime
 *
 * @package IdeasBucket\QueueBundle\Util
 */
trait InteractsWithTimeTrait
{
    /**
     * Get the number of seconds until the given DateTime.
     *
     * @param  \DateTimeInterface  $delay
     *
     * @return int
     */
    protected function secondsUntil($delay)
    {
        return $delay instanceof \DateTimeInterface ? max(0, $delay->getTimestamp() - time()): (int) $delay;
    }
    /**
     * Get the "available at" UNIX timestamp.
     *
     * @param  \DateTimeInterface|int  $delay
     *
     * @return int
     */
    protected function availableAt($delay = 0)
    {
        return $delay instanceof \DateTimeInterface ? $delay->getTimestamp() : (time() + $delay);
    }

    /**
     * Get the current system time as a UNIX timestamp.
     *
     * @return int
     */
    protected function currentTime()
    {
        return time();
    }
}