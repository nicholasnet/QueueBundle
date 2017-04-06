<?php

namespace IdeasBucket\QueueBundle\Command;

use IdeasBucket\QueueBundle\Manager;
use IdeasBucket\QueueBundle\Repository\FailedJobRepositoryInterface;
use IdeasBucket\QueueBundle\Type\DatabaseQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RetryCommand
 *
 * @package IdeasBucket\QueueBundle\Command
 */
class RetryCommand extends Command
{
    use CheckForFailRepositoryTrait;

    /**
     * Interface which provides necessary construct for handling the failed job.
     *
     * @var FailedJobRepositoryInterface $failed
     */
    private $failed;

    /**
     * The queue manager.
     *
     * @var Manager $queue
     */
    private $queue;

    /**
     * This function constructs the RetryCommand object.
     *
     * @param Manager                  $queue  The queue manager.
     * @param FailedJobRepositoryInterface $failed Interface which provides necessary construct for handling the failed job.
     */
    public function __construct(Manager $queue, FailedJobRepositoryInterface $failed = null)
    {
        parent::__construct();

        $this->failed = $failed;
        $this->queue = $queue;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('idb_queue:retry')
             ->setDescription('Retry a failed queue job')
             ->addArgument('id', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'The ID(s) of the failed job');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fire($input, $output, function(InputInterface $input, OutputInterface $output) {

            $ids = $input->getArgument('id');

            $failedJobs = ($ids === 'all') ? $this->failed->findAll() : $this->failed->findByIds($ids);

            // If entity is found.
            if (!empty($failedJobs)) {

                foreach ($failedJobs as $failedJob) {

                    $failedJob->setPayload($this->resetAttempts($failedJob->getPayload()));

                    // Queue the job again. Here payload is an array so we need to decode it as an valid json string back
                    // again.
                    $queue = $this->queue->connection($failedJob->getConnection());

                    if ($queue instanceof DatabaseQueue) {

                        $queue->pushRaw($failedJob->getPayload(), $failedJob->getQueue());

                    } else {

                        $queue->pushRaw(json_encode($failedJob->getPayload()), $failedJob->getQueue());
                    }


                    $this->failed->forget($failedJob);
                    $output->writeln('The failed job ID #' . $failedJob->getId() . ' has been pushed back onto the queue!');
                }

            } else {

                $output->writeln('No failed job matches the given ID.');

            }

        }, $this->failed);
    }

    /**
     * Reset the attempts key in payload if it is present.
     *
     * @param  array $payload The payload.
     *
     * @return array The payload.
     */
    protected function resetAttempts($payload)
    {
        if (isset($payload['attempts'])) {

            $payload['attempts'] = 0;
        }

        return $payload;
    }
}