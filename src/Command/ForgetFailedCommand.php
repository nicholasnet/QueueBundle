<?php

namespace IdeasBucket\QueueBundle\Command;

use IdeasBucket\QueueBundle\Repository\FailedJobRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ForgetFailedCommand
 *
 * @package IdeasBucket\QueueBundle\Command
 */
class ForgetFailedCommand extends Command
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
        $this->setName('idb_queue:forget')
             ->setDescription('Delete a failed queue job')
             ->addArgument('id', InputArgument::REQUIRED, 'The ID of the failed job');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fire($input, $output, function(InputInterface $input, OutputInterface $output) {

            $id = $input->getArgument('id');

            $failedJob = $this->failed->findByIds([$id]);

            if (empty($failedJob)) {

                $output->writeln('<error>No Job found under that ID #'. $id .'</error>');
            }

            $this->failed->forget($failedJob[0]);
            $output->writeln('Job deleted successfully.');

        }, $this->failed);
    }
}
