<?php

namespace IdeasBucket\QueueBundle\Type;

use IdeasBucket\QueueBundle\Job\BeanstalkdJob;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Job as PheanstalkJob;

/**
 * Class BeanstalkdQueue
 *
 * @package IdeasBucket\QueueBundle\Types
 */
class BeanstalkdQueue extends AbstractQueue implements QueueInterface
{
    /**
     * The Pheanstalk instance.
     *
     * @var \Pheanstalk\Pheanstalk
     */
    protected $pheanstalk;

    /**
     * The name of the default tube.
     *
     * @var string
     */
    protected $default;

    /**
     * The "time to run" for all pushed jobs.
     *
     * @var int
     */
    protected $timeToRun;

    /**
     * Create a new Beanstalkd queue instance.
     *
     * @param Pheanstalk $pheanstalk
     * @param string     $default
     * @param int        $timeToRun
     */
    public function __construct(Pheanstalk $pheanstalk, $default, $timeToRun)
    {
        $this->default = $default;
        $this->timeToRun = $timeToRun;
        $this->pheanstalk = $pheanstalk;
    }

    /**
     * @inheritDoc
     */
    public function size($queue = null)
    {
        $queue = $this->getQueue($queue);

        return (int)$this->pheanstalk->statsTube($queue)->current_jobs_ready;
    }

    /**
     * @inheritDoc
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * @inheritDoc
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->pheanstalk->useTube($this->getQueue($queue))->put(

            $payload, Pheanstalk::DEFAULT_PRIORITY, Pheanstalk::DEFAULT_DELAY, $this->timeToRun
        );
    }

    /**
     * @inheritDoc
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $pheanstalk = $this->pheanstalk->useTube($this->getQueue($queue));

        return $pheanstalk->put(

            $this->createPayload($job, $data),
            Pheanstalk::DEFAULT_PRIORITY,
            $this->secondsUntil($delay),
            $this->timeToRun
        );
    }

    /**
     * @inheritDoc
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);
        $job = $this->pheanstalk->watchOnly($queue)->reserve(0);

        if ($job instanceof PheanstalkJob) {

            return new BeanstalkdJob($this->container, $this->pheanstalk, $job, $this->connectionName, $queue);
        }
    }

    /**
     * Delete a message from the Beanstalk queue.
     *
     * @param string $queue
     * @param string $id
     */
    public function deleteMessage($queue, $id)
    {
        $queue = $this->getQueue($queue);
        $this->pheanstalk->useTube($queue)->delete(new PheanstalkJob($id, ''));
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
        return $queue ?: $this->default;
    }

    /**
     * Get the underlying Pheanstalk instance.
     *
     * @return Pheanstalk
     */
    public function getPheanstalk()
    {
        return $this->pheanstalk;
    }
}
