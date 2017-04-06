<?php

namespace IdeasBucket\QueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class IdeasBucketQueueBundlePass implements CompilerPassInterface
{
    /**
     * This method processes the container definition. In this case we are processing just the ideasbucket_queue.connector
     * definition.
     *
     * @param ContainerBuilder $container The container builder.
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ideasbucket_queue')) {

            return;
        }

        $serviceDefinition = $container->getDefinition('ideasbucket_queue');
        $tagged = $container->findTaggedServiceIds('ideasbucket_queue.connector');

        foreach ($tagged as $id => $attr) {

            $serviceDefinition->addMethodCall('addConnector', [new Reference($id)]);
        }
    }
}
