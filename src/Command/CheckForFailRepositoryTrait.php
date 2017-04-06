<?php

namespace IdeasBucket\QueueBundle\Command;

use IdeasBucket\QueueBundle\Repository\FailedJobRepositoryInterface as Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckForFailRepositoryTrait
 *
 * @package IdeasBucket\QueueBundle\Command
 */
trait CheckForFailRepositoryTrait
{
    protected function fire(InputInterface $input, OutputInterface $output, Callable $callback, Logger $failed = null)
    {
        if ($failed !== null) {

            $callback($input, $output);

        } else {

            $message = '<error>Service ideasbucket_queue.failed_repository not configured. '.
                       'You need to define service in config under ideasbucket_queue -> failed_job_repository</error>';

            $output->writeln($message);
        }
    }
}