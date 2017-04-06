<?php

namespace IdeasBucket\QueueBundle;

use IdeasBucket\QueueBundle\DependencyInjection\Compiler\IdeasBucketQueueBundlePass;
use IdeasBucket\QueueBundle\DependencyInjection\IdeasBucketQueueExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class IdeasBucketQueueBundle
 *
 * @package IdeasBucket\QueueBundle
 */
class IdeasBucketQueueBundle extends Bundle
{
    /**
     * @inheritdoc
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new IdeasBucketQueueBundlePass);
    }

    /**
     * @inheritdoc
     */
    protected function createContainerExtension()
    {
        return new IdeasBucketQueueExtension;
    }

    /**
     * @inheritdoc
     */
    protected function getContainerExtensionClass()
    {
        return IdeasBucketQueueExtension::class;
    }

    /**
     * @inheritdoc
     */
    public function getContainerExtension()
    {
        if (null === $this->extension) {

            $extension = $this->createContainerExtension();
            $this->extension = $extension;
        }

        return $this->extension;
    }
}
