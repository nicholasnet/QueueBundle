<?php

namespace IdeasBucket\QueueBundle\Job;

use IdeasBucket\QueueBundle\QueueableInterface;
use IdeasBucket\QueueBundle\Type\RedisQueue;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

class RedisJobTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getContainer()->shouldReceive('has')->once()->with('foo')->andReturn(true);
        $job->getContainer()->shouldReceive('get')->once()->with('foo')->andReturn($handler = m::mock(QueueableInterface::class));

        $handler->shouldReceive('fire')->once()->with($job, ['data']);
        $job->fire();
    }

    public function testDeleteRemovesTheJobFromRedis()
    {
        $job = $this->getJob();
        $job->getRedisQueue()->shouldReceive('deleteReserved')->once()
            ->with('default', $job);
        $job->delete();
    }

    public function testReleaseProperlyReleasesJobOntoRedis()
    {
        $job = $this->getJob();
        $job->getRedisQueue()->shouldReceive('deleteAndRelease')->once()
            ->with('default', $job, 1);
        $job->release(1);
    }

    protected function getJob()
    {
        return new RedisJob(
            m::mock(Container::class),
            m::mock(RedisQueue::class),
            json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 1]),
            json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 2]),
            'default'
        );
    }
}
