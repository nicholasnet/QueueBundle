<?php

namespace IdeasBucket\QueueBundle;

use IdeasBucket\QueueBundle\Job\JobsInterface;

/**
 * Class InteractsWithQueueInterface
 *
 * @package IdeasBucket\QueueBundle
 */
interface QueueableInterface
{
    /**
     * @param JobsInterface $job
     * @param mixed         $data
     */
    public function fire(JobsInterface $job, $data = null);
}