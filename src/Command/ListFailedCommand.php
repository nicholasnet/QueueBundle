<?php

namespace IdeasBucket\QueueBundle\Command;

use IdeasBucket\QueueBundle\Repository\FailedJobRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * Class ListFailedCommand
 *
 * @package IdeasBucket\QueueBundle\Command
 */
class ListFailedCommand extends Command
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
        $this->setName('idb_queue:failed')->setDescription('List all of the failed queue jobs');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fire($input, $output, function(InputInterface $input, OutputInterface $output) {

            $rows = [];

            foreach ($this->failed->findAll() as $failed) {

                $rows[] = [
                    $failed->getId(),
                    $failed->getConnection(),
                    $failed->getQueue(),
                    $failed->getPayload()['job'],
                    $failed->getFailedAt()->format(\DateTime::RFC850)
                ];
            }

            if (count($rows) === 0) {

                return $output->writeln('No failed jobs!');
            }

            (new Table($output))->setHeaders(['ID', 'Connection', 'Queue', 'Service', 'Failed At'])
                                ->setRows($rows)
                                ->render();

        }, $this->failed);
    }
}
