<?php

namespace IdeasBucket\QueueBundle\Type;

use IdeasBucket\QueueBundle\QueueableInterface;
use Mockery as m;
use Predis\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RedisQueueTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushProperlyPushesJobOntoRedis()
    {
        $queue = $this->getMockBuilder(RedisQueue::class)->setMethods(['getRandomId'])->setConstructorArgs([$redis = m::mock(Client::class), 'default'])->getMock();

        $queue->setContainer($this->getContainer());

        $queue->expects($this->once())->method('getRandomId')->will($this->returnValue('foo'));
        $redis->shouldReceive('rpush')->once()->with('queues:default', '{"job":"foo","data":["data"],"maxTries":null,"timeout":null,"timeoutAt":null,"id":"foo","attempts":0}');
        $id = $queue->push('foo', ['data']);
        $this->assertEquals('foo', $id);
    }

    public function testDelayedPushProperlyPushesJobOntoRedis()
    {
        $queue = $this->getMockBuilder(RedisQueue::class)->setMethods(['availableAt', 'getRandomId'])->setConstructorArgs([$redis = m::mock(Client::class), 'default'])->getMock();
        $queue->setContainer($this->getContainer());
        $queue->expects($this->once())->method('getRandomId')->will($this->returnValue('foo'));
        $queue->expects($this->once())->method('availableAt')->with(1)->will($this->returnValue(2));
        $redis->shouldReceive('zadd')->once()->with(
            'queues:default:delayed',
            2,
            '{"job":"foo","data":["data"],"maxTries":null,"timeout":null,"timeoutAt":null,"id":"foo","attempts":0}'
        );
        $id = $queue->later(1, 'foo', ['data']);
        $this->assertEquals('foo', $id);
    }

    public function testDelayedPushWithDateTimeProperlyPushesJobOntoRedis()
    {
        $date = new \DateTime();
        $queue = $this->getMockBuilder(RedisQueue::class)->setMethods(['availableAt', 'getRandomId'])->setConstructorArgs([$redis = m::mock(Client::class), 'default'])->getMock();
        $queue->setContainer($this->getContainer());
        $queue->expects($this->once())->method('getRandomId')->will($this->returnValue('foo'));
        $queue->expects($this->once())->method('availableAt')->with($date)->will($this->returnValue(2));
        $redis->shouldReceive('zadd')->once()->with(
            'queues:default:delayed',
            2,
            '{"job":"foo","data":["data"],"maxTries":null,"timeout":null,"timeoutAt":null,"id":"foo","attempts":0}'
        );
        $queue->later($date, 'foo', ['data']);
    }

    private function getContainer()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')->once()->with('foo')->andReturn(true);
        $container->shouldReceive('get')->once()->with('foo')->andReturn(m::mock(QueueableInterface::class));

        return $container;
    }
}
