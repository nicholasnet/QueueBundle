<?php

namespace IdeasBucket\QueueBundle\Command;

use IdeasBucket\QueueBundle\Manager;
use IdeasBucket\QueueBundle\Util\SwitchInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class QueueStartCommand
 *
 * @package IdeasBucket\QueueBundle\Command;
 */
class QueueStartCommand extends Command
{
    /**
     * @var SwitchInterface
     */
    private $switchInterface;

    /**
     * QueueStartCommand constructor.
     *
     * @param SwitchInterface $switchInterface
     */
    public function __construct(SwitchInterface $switchInterface)
    {
        $this->switchInterface = $switchInterface;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('idb_queue:worker_start')->setDescription('Turn on the queue workers.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->switchInterface->turnOff(Manager::LOCK_NAME);
    }
}
