<?php

namespace IdeasBucket\QueueBundle\DependencyInjection;

use IdeasBucket\QueueBundle\Util\FileSystemSwitch;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class IdeasBucketQueueExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $mergedConfiguration = $this->processConfiguration($configuration, $configs);

        $container->setParameter('ideasbucket_queue.command_path', $mergedConfiguration['command_path']);
        $container->setParameter('ideasbucket_queue.configuration', $mergedConfiguration);
        $container->setParameter('ideasbucket_queue.cache_driver', $mergedConfiguration['cache_handler']);

        if (!empty($mergedConfiguration['failed_job_repository'])) {

            $container->setAlias('ideasbucket_queue.failed_repository', $mergedConfiguration['failed_job_repository']);
        }

        if (!empty($mergedConfiguration['connections']['database']['repository'])) {

            $container->setAlias('ideasbucket_queue.database_repository', $mergedConfiguration['connections']['database']['repository']);
        }

        if (!empty($mergedConfiguration['connections']['redis']['client'])) {

            $container->setAlias('ideasbucket_queue.redis_client', $mergedConfiguration['connections']['redis']['client']);
        }

        $container->setAlias('idb_queue', 'ideasbucket_queue');

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('connectors.xml');
        $loader->load('commands.xml');
        $this->configureLockService($mergedConfiguration, $container);
    }

    /**
     * @param array            $configuration
     * @param ContainerBuilder $container
     */
    protected function configureLockService($configuration, ContainerBuilder $container)
    {
        if (empty($configuration['lock_service'])) {

            throw new InvalidConfigurationException('lock_service option in queue bundle cannot be empty.');
        }

        $serviceName = $configuration['lock_service'];

        if ($serviceName === 'ideasbucket_queue.filesystem_switch') {

            // Define lock path and configure service
            $optionDef = new Definition(FileSystemSwitch::class);

            if (empty($configuration['lock_path'])) {

                $lockPath = $container->getParameter('kernel.cache_dir') . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lock';

            } else {

                $lockPath = $configuration['lock_path'];
            }

            $optionDef->addArgument($lockPath);
            $container->setDefinition('ideasbucket_queue.switch_service', $optionDef);

        } else {

            // Set the service alias.
            $container->setAlias('ideasbucket_queue.switch_service', $serviceName);
        }
    }

    public function getAlias()
    {
        return 'ideasbucket_queue';
    }
}
