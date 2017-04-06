<?php

namespace IdeasBucket\QueueBundle\Event;

/**
 * Class EventsList
 *
 * @package IdeasBucket\QueueBundle\Events
 */
final class EventsList
{
    const JOB_EXCEPTION_OCCURRED = 'job_exception_occurred';

    const JOB_FAILED = 'job_failed';

    const JOB_PROCESSED = 'job_processed';

    const JOB_PROCESSING = 'job_processing';

    const LOOPING = 'looping';

    const WORKER_STOPPING = 'worker_stopping';

    /**
     * @return array
     */
    public function getList()
    {
        return [
            self::JOB_EXCEPTION_OCCURRED,
            self::JOB_FAILED,
            self::JOB_PROCESSED,
            self::JOB_PROCESSING,
            self::LOOPING,
            self::WORKER_STOPPING
        ];
    }
}