<?php

namespace IdeasBucket\QueueBundle\Command;

use Symfony\Component\Console\Command\Command;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RestartCommand
 *
 * @package IdeasBucket\QueueBundle\Command
 */
class RestartCommand extends Command
{
    /**
     * Interface that provides the cache implementation.
     *
     * @var CacheInterface $cache
     */
    protected $cache;

    /**
     * This function constructs the RestartCommand object.
     *
     * @param CacheInterface $cache The cache interface.
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('idb_queue:restart')->setDescription('Restart queue worker daemons after their current job.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->cache !== null) {

            $this->cache->set('ideasbucket_queue:restart', time());
            $output->writeln('Broadcasting queue restart signal.');

        } else {

            $output->writeln('Cache service is not setup.');
        }
    }
}
