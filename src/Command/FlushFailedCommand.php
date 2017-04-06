<?php

namespace IdeasBucket\QueueBundle\Command;

use IdeasBucket\QueueBundle\Repository\FailedJobRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class FlushFailedCommand
 *
 * @package IdeasBucket\QueueBundle\Command
 */
class FlushFailedCommand extends Command
{
    use CheckForFailRepositoryTrait;

    /**
     * Interface which provides necessary construct for handling the failed job.
     *
     * @var FailedJobRepositoryInterface $failed
     */
    private $failed;

    /**
     * This function constructs the ForgetFailedCommand object.
     *
     * @param FailedJobRepositoryInterface $failed A contract which defines method that must be implement by any Failed
     *                                         job provider.
     */
    public function __construct(FailedJobRepositoryInterface $failed = null)
    {
        $this->failed = $failed;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('idb_queue:flush')->setDescription('Flush all of the failed queue jobs');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fire($input, $output, function(InputInterface $input, OutputInterface $output) {

            $qtyOfDeletedJob = $this->failed->flush();

            // Affected quantity is more than 0.
            if ($qtyOfDeletedJob > 0) {

                $output->writeln($qtyOfDeletedJob . ' job deleted successfully.');

            } else {

                $output->writeln('No jobs were deleted.');

            }

        }, $this->failed);
    }
}
