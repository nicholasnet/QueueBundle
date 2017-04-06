<?php

namespace IdeasBucket\QueueBundle;

use IdeasBucket\QueueBundle\Connector\ConnectorInterface;
use IdeasBucket\QueueBundle\Connector\NullConnector;
use IdeasBucket\QueueBundle\Connector\SyncConnector;
use IdeasBucket\QueueBundle\Type\QueueInterface;
use IdeasBucket\QueueBundle\Type\SyncQueue;
use IdeasBucket\QueueBundle\Util\SwitchInterface;
use PHPUnit\Framework\TestCase;
use Mockery as m;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ManagerTest extends TestCase
{
    private $container;
    private $dispatcher;
    private $switch;

    public function tearDown()
    {
        m::close();
    }

    public function setUp()
    {
        parent::setUp();

        $this->container = m::mock(ContainerInterface::class);
        $this->container->shouldReceive('get')->withAnyArgs();
        $this->dispatcher = m::mock(EventDispatcher::class);
        $this->switch = m::mock(SwitchInterface::class);
    }

    public function testDefaultConnectionCanBeResolved()
    {
        $app = ['default' => 'sync'];

        $container = m::mock(ContainerInterface::class);

        $queue = m::mock(SyncQueue::class, [$this->dispatcher]);
        $queue->shouldReceive('setContainer')->with($container);

        $manager = new Manager($container, $app, $this->dispatcher, $this->switch);
        $connector = m::mock(SyncConnector::class);
        $connector->shouldReceive('connect')->withArgs([$container, ['driver' => 'sync']])->andReturn($queue);
        $connector->shouldReceive('getName')->andReturn('sync');

        $manager->addConnector($connector);

        $this->assertSame($queue, $manager->connection('sync'));
    }

    public function testOtherConnectionCanBeResolved()
    {
        $app = ['connections' => ['foo' => ['driver' => 'foo']]];

        $container = m::mock(ContainerInterface::class);

        $queue = m::mock(QueueInterface::class);
        $queue->shouldReceive('setContainer')->with($container);

        $manager = new Manager($container, $app, $this->dispatcher, $this->switch);
        $connector = m::mock(SyncConnector::class);
        $connector->shouldReceive('connect')->withArgs([$container, ['driver' => 'foo']])->andReturn($queue);
        $connector->shouldReceive('getName')->andReturn('foo');

        $manager->addConnector($connector);

        $this->assertSame($queue, $manager->connection('foo'));
    }

    public function testNullConnectionCanBeResolved()
    {
        $app = ['default' => 'null'];

        $container = m::mock(ContainerInterface::class);

        $queue = m::mock(QueueInterface::class);
        $queue->shouldReceive('setContainer')->with($container);

        $manager = new Manager($container, $app, $this->dispatcher, $this->switch);
        $connector = m::mock(NullConnector::class);
        $connector->shouldReceive('connect')->withArgs([$container, ['driver' => 'null']])->andReturn($queue);
        $connector->shouldReceive('getName')->andReturn('null');

        $manager->addConnector($connector);

        $this->assertSame($queue, $manager->connection('null'));
    }
}
