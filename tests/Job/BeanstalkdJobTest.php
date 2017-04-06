<?php

namespace IdeasBucket\QueueBundle\Job;

use IdeasBucket\QueueBundle\QueueableInterface;
use IdeasBucket\QueueBundle\QueueErrorInterface;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

class BeanstalkdJobTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getPheanstalkJob()->shouldReceive('getData')->once()->andReturn(json_encode(['job' => 'foo', 'data' => ['data']]));
        $job->getContainer()
            ->shouldReceive('has')->once()->with('foo')->andReturn(true)
            ->shouldReceive('get')->once()->with('foo')->andReturn($handler = m::mock(QueueableInterface::class));
        $handler->shouldReceive('fire')->once()->with($job, ['data']);
        $job->fire();
    }

    public function testFailedProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getPheanstalkJob()->shouldReceive('getData')->once()->andReturn(json_encode(['job' => 'foo', 'data' => ['data']]));

        $job->getContainer()
            ->shouldReceive('has')->once()->with('foo')->andReturn(true)
            ->shouldReceive('get')->once()->with('foo')->andReturn($handler = m::mock(BeanstalkdJobTestFailedTest::class));
        $handler->shouldReceive('failed')->once()->with(m::type('Exception'), ['data']);
        $job->failed(new \Exception);
    }

    public function testDeleteRemovesTheJobFromBeanstalkd()
    {
        $job = $this->getJob();
        $job->getPheanstalk()->shouldReceive('delete')->once()->with($job->getPheanstalkJob());
        $job->delete();
    }

    public function testReleaseProperlyReleasesJobOntoBeanstalkd()
    {
        $job = $this->getJob();
        $job->getPheanstalk()->shouldReceive('release')->once()->with($job->getPheanstalkJob(), \Pheanstalk\Pheanstalk::DEFAULT_PRIORITY, 0);
        $job->release();
    }

    public function testBuryProperlyBuryTheJobFromBeanstalkd()
    {
        $job = $this->getJob();
        $job->getPheanstalk()->shouldReceive('bury')->once()->with($job->getPheanstalkJob());
        $job->bury();
    }

    protected function getJob()
    {
        return new BeanstalkdJob(
            m::mock(Container::class),
            m::mock('Pheanstalk\Pheanstalk'),
            m::mock('Pheanstalk\Job'),
            'connection-name',
            'default'
        );
    }
}

class BeanstalkdJobTestFailedTest implements QueueErrorInterface, QueueableInterface
{
    public function failed(\Exception $e, $payload = null)
    {
        //
    }

    public function fire(JobsInterface $job, $data = null)
    {
        // TODO: Implement fire() method.
    }
}