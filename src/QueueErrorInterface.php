<?php

namespace IdeasBucket\QueueBundle;

/**
 * Class CanBeQueuedWithErrorHandlerInterface
 *
 * @package IdeasBucket\QueueBundle
 */
interface QueueErrorInterface
{
    /**
     * @param \Exception $e
     * @param mixed      $payload
     */
    public function failed(\Exception $e, $payload = null);
}