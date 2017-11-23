<?php

namespace IdeasBucket\QueueBundle\Type;

use IdeasBucket\QueueBundle\Job\BeanstalkdJob;
use IdeasBucket\QueueBundle\Job\JobsInterface;
use IdeasBucket\QueueBundle\QueueableInterface;
use PHPUnit\Framework\TestCase;
use Mockery as m;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BeanstalkdQueueTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushProperlyPushesJobOntoBeanstalkd()
    {
        $queue = new BeanstalkdQueue(m::mock('Pheanstalk\Pheanstalk'), 'default', 60);
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')->times(2)->with('foo')->andReturn(true);
        $container->shouldReceive('get')->times(2)->with('foo')->andReturn(new DummyJob);
        $queue->setContainer($container);
        $pheanstalk = $queue->getPheanstalk();

        $pheanstalk->shouldReceive('useTube')->once()->with('stack')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('put')->twice()->with(json_encode(['job' => 'foo', 'data' => ['data'], 'maxTries' => null, 'timeout' => null, 'timeoutAt' => null]), 1024, 0, 60);
        $queue->push('foo', ['data'], 'stack');
        $queue->push('foo', ['data']);
    }

    public function testDelayedPushProperlyPushesJobOntoBeanstalkd()
    {
        $queue = new BeanstalkdQueue(m::mock('Pheanstalk\Pheanstalk'), 'default', 60);
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')->times(2)->with('foo')->andReturn(true);
        $container->shouldReceive('get')->times(2)->with('foo')->andReturn(new DummyJobWithPublicProperty);
        $queue->setContainer($container);
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('useTube')->once()->with('stack')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('put')->twice()->with(json_encode(['job' => 'foo', 'data' => ['data'], 'maxTries' => 1, 'timeout' => 1, 'timeoutAt' => null]), \Pheanstalk\Pheanstalk::DEFAULT_PRIORITY, 5, \Pheanstalk\Pheanstalk::DEFAULT_TTR);
        $queue->later(5, 'foo', ['data'], 'stack');
        $queue->later(5, 'foo', ['data']);
    }

    public function testPopProperlyPopsJobOffOfBeanstalkd()
    {
        $queue = new BeanstalkdQueue(m::mock('Pheanstalk\Pheanstalk'), 'default', 60);
        $queue->setContainer(m::mock(Container::class));
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('watchOnly')->once()->with('default')->andReturn($pheanstalk);
        $job = m::mock('Pheanstalk\Job');
        $pheanstalk->shouldReceive('reserve')->once()->andReturn($job);
        $result = $queue->pop();
        $this->assertInstanceOf(BeanstalkdJob::class, $result);
    }

    public function testDeleteProperlyRemoveJobsOffBeanstalkd()
    {
        $queue = new BeanstalkdQueue(m::mock('Pheanstalk\Pheanstalk'), 'default', 60);
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('delete')->once()->with(m::type('Pheanstalk\Job'));
        $queue->deleteMessage('default', 1);
    }
}

class DummyJob implements QueueableInterface
{
    private $maxTries = null;

    private $timeout = null;

    public function fire(JobsInterface $job, $data = null)
    {

    }

    public function getMaxTries()
    {
        return $this->maxTries;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }
}

class DummyJobWithPublicProperty implements QueueableInterface
{
    public $maxTries = 1;
    public $timeout = 1;

    public function fire(JobsInterface $job, $data = null)
    {

    }
}
