<?php

namespace IdeasBucket\QueueBundle\Command;

use IdeasBucket\QueueBundle\Listener;
use IdeasBucket\QueueBundle\ListenerOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class ListenCommand
 *
 * @package IdeasBucket\QueueBundle\Command
 */
class ListenCommand extends Command
{
    /**
     * Listener which listens for the incoming job.
     *
     * @var Listener $listener
     */
    private $listener;

    /**
     * The queue configuration.
     *
     * @var array $queueConfiguration
     */
    private $queueConfiguration;

    /**
     * ListenCommand constructor.
     *
     * @param Listener $listener
     * @param array    $queueConfiguration
     */
    public function __construct(Listener $listener, array $queueConfiguration)
    {
        $this->listener = $listener;
        $this->queueConfiguration = $queueConfiguration;
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('idb_queue:listen')
             ->setDescription('Listen to a given queue')
             ->addArgument('connection', InputArgument::OPTIONAL, 'The name of connection')
             ->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'The queue to listen on', null)
             ->addOption('delay', null, InputOption::VALUE_OPTIONAL, 'Amount of time to delay failed jobs', 0)
             ->addOption('env', null, InputOption::VALUE_OPTIONAL, 'Environment that we need to run in', null)
             ->addOption('memory', null, InputOption::VALUE_OPTIONAL, 'The memory limit in megabytes', 128)
             ->addOption('timeout', null, InputOption::VALUE_OPTIONAL, 'Seconds a job may run before timing out', 60)
             ->addOption('sleep', null, InputOption::VALUE_OPTIONAL, 'Seconds to wait before checking queue for jobs', 3)
             ->addOption('force', null, InputOption::VALUE_OPTIONAL, 'Force the worker to run even in maintenance mode', false)
             ->addOption('tries', null, InputOption::VALUE_OPTIONAL, 'Number of times to attempt a job before logging it failed', 3);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOutputHandler($this->listener, $output);
        $connection = $input->getArgument('connection');

        // We need to get the right queue for the connection which is set in the queue
        // configuration file for the application. We will pull it based on the set
        // connection being run for the queue operation currently being executed.
        $queue = $this->getQueue($input->getOption('queue'), $connection);

        if ($queue === null) {

            $output->writeln('<error>Queue name cannot be null also you cannot listen Sync and Null queue.</error>');

        } else {

            $this->listener->listen($connection, $queue, $this->gatherOptions($input));
        }
    }

    /**
     * Get the queue name for the worker.
     *
     * @param  string $queueName
     * @param  string $connection
     *
     * @return string
     */
    protected function getQueue($queueName, $connection)
    {
        if ($queueName !== null) {

            return $queueName;
        }

        if ($connection === null) {

            $queue = $this->queueConfiguration['default'];

            if (($queue === 'sync') || ($queue === 'null')) {

                return null;
            }

            return $this->queueConfiguration['connections'][$queue]['queue'];

        } elseif ($connection !== null) {

            if (($connection === 'sync') || ($connection === 'null')) {

                return null;
            }

            return $this->queueConfiguration['connections'][$connection]['queue'];
        }

        return null;
    }

    /**
     * Get the listener options for the command.
     *
     * @param InputInterface $input
     *
     * @return ListenerOptions
     */
    protected function gatherOptions(InputInterface $input)
    {
        return new ListenerOptions(
            $input->getOption('env'),
            $input->getOption('delay'),
            $input->getOption('memory'),
            $input->getOption('timeout'),
            $input->getOption('sleep'),
            $input->getOption('tries'),
            $input->getOption('force')
        );
    }

    /**
     * Set the options on the queue listener.
     *
     * @param  Listener       $listener
     * @param OutputInterface $output
     */
    protected function setOutputHandler(Listener $listener, OutputInterface $output)
    {
        $listener->setOutputHandler(function ($type, $line) use ($output) {

            $output->write($line);
        });
    }
}
