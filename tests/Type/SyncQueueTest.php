<?php

namespace IdeasBucket\QueueBundle\Type;

use IdeasBucket\QueueBundle\QueueableInterface;
use IdeasBucket\QueueBundle\QueueErrorInterface;
use IdeasBucket\QueueBundle\Job\JobsInterface;
use IdeasBucket\QueueBundle\Job\SyncJob;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;


class SyncQueueTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushShouldFireJobInstantly()
    {
        unset($_SERVER['__sync.test']);

        $sync = new SyncQueue(m::mock(EventDispatcher::class)->shouldIgnoreMissing());

        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')->andReturn(true);
        $container->shouldReceive('get')->with('foo')->andReturn(new SyncQueueTestHandler);

        $sync->setContainer($container);
        $sync->push('foo', ['foo' => 'bar']);

        $this->assertInstanceOf(SyncJob::class, $_SERVER['__sync.test'][0]);
        $this->assertEquals(['foo' => 'bar'], $_SERVER['__sync.test'][1]);
    }

    public function testFailedJobGetsHandledWhenAnExceptionIsThrown()
    {
        unset($_SERVER['__sync.failed']);

        $eventDispatcher = m::mock(EventDispatcher::class);
        $eventDispatcher->shouldReceive('dispatch')->times(3);

        $sync = new SyncQueue($eventDispatcher);

        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')->andReturn(true);
        $container->shouldReceive('get')->with('foo')->andReturn(new FailingSyncQueueTest);
        $sync->setContainer($container);

        try {

            $sync->push('foo', ['foo' => 'bar']);

        } catch (\Exception $e) {

            $this->assertTrue($_SERVER['__sync.failed']);
        }
    }
}

class SyncQueueTestEntity
{
    public function getQueueableId()
    {
        return 1;
    }
}

class SyncQueueTestHandler implements QueueableInterface
{
    public function fire(JobsInterface $job, $data = null)
    {
        $_SERVER['__sync.test'] = func_get_args();
    }
}

class FailingSyncQueueTest implements QueueErrorInterface, QueueableInterface
{
    public function fire(JobsInterface $job, $data = null)
    {
        throw new \Exception();
    }

    public function failed(\Exception $e, $payload = null)
    {
        $_SERVER['__sync.failed'] = true;
    }
}