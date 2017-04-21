<?php

namespace IdeasBucket\QueueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder;
        $rootNode = $treeBuilder->root('ideasbucket_queue');
        $drivers = ['sync', 'null', 'beanstalkd', 'sqs', 'redis', 'database'];

        $rootNode
            ->validate()
                ->ifTrue(function ($v) {

                    if (!isset($v['connections'])) {

                        return false;
                    }

                    foreach ($v['connections'] as $name => $connection) {

                        if (in_array($name, ['redis', 'sqs', 'database', 'beanstalkd']) && (empty($connection['queue']))) {

                            return true;
                        }
                    }

                    return false;
                })
                ->thenInvalid('Queue configuration must be defined for sqs, redis, beanstalkd and database drivers.')
            ->end()
            ->validate()
            ->ifTrue(function ($v) {

                if (!isset($v['connections'])) {

                    return false;
                }

                foreach ($v['connections'] as $name => $connection) {

                    if (in_array($name, ['redis']) && (empty($connection['client']))) {

                        return true;
                    }
                }

                return false;
            })
            ->thenInvalid('Redis connectors must have client configuration defined.')
            ->end()
            ->validate()
            ->ifTrue(function ($v) {

                if (!isset($v['connections'])) {

                    return false;
                }

                foreach ($v['connections'] as $name => $connection) {

                    if (in_array($name, ['redis', 'beanstalkd']) && (empty($connection['retry_after']))) {

                        return true;
                    }
                }

                return false;
            })
            ->thenInvalid('Retry after configuration is missing which is required to Redis and Beanstalkd connection.')
            ->end()
            ->validate()
                ->ifTrue(function($v) {

                    return (!empty($v['connections']['sqs']) && (empty($v['connections']['sqs']['prefix'])));
                })
                ->thenInvalid('SQS driver configuration must have prefix defined.')
            ->end()
            ->validate()
            ->ifTrue(function($v) {

                return (!empty($v['connections']['database']) && (empty($v['connections']['database']['repository'])));
            })
            ->thenInvalid('For database driver repository must be defined.')
            ->end()
            ->children()
                ->enumNode('default')->values($drivers)->defaultValue('sync')->end()
                ->scalarNode('lock_path')
                    ->defaultNull()
                    ->info('Use this setting to define path to store the lock.')
                ->end()
                ->scalarNode('lock_service')
                    ->defaultValue('ideasbucket_queue.filesystem_switch')
                    ->info('Use this setting to define custom lock service.')
                ->end()
                ->scalarNode('failed_job_repository')->info('Repository for storing failed job.')->defaultNull()->end()
                ->scalarNode('command_path')
                    ->defaultValue('%kernel.root_dir%/../bin/')
                    ->info('Use this setting to define path console path.')
                ->end()
                ->scalarNode('cache_handler')->isRequired()->info('Cache handler.')->end()
                ->arrayNode('connections')->prototype('array')
                    ->children()
                        ->scalarNode('driver')->isRequired()->cannotBeEmpty()
                            ->validate()
                                ->ifNotInArray($drivers)
                                ->thenInvalid('Invalid queue driver "%s". Supported drivers are ' . implode(', ', $drivers))
                            ->end()
                        ->end()
                        ->scalarNode('queue')->end()
                        ->scalarNode('key')->end()
                        ->scalarNode('client')->end()
                        ->integerNode('retry_after')->end()
                        ->scalarNode('secret')->end()
                        ->scalarNode('region')->end()
                        ->booleanNode('persistent')->defaultFalse()->end()
                        ->scalarNode('host')->end()
                        ->scalarNode('repository')->end()
                        ->scalarNode('prefix')->end()
                        ->scalarNode('endpoint')->end()
                        ->integerNode('timeout')->end()
                        ->integerNode('port')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
