<?php

namespace IdeasBucket\QueueBundle;

use PHPUnit\Framework\TestCase;
use Mockery as m;

class ListenerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRunProcessCallsProcess()
    {
        $process = m::mock('Symfony\Component\Process\Process')->makePartial();
        $process->shouldReceive('run')->once();
        $listener = m::mock(Listener::class)->makePartial();
        $listener->shouldReceive('memoryExceeded')->once()->with(1)->andReturn(false);

        $listener->runProcess($process, 1);
    }

    public function testListenerStopsWhenMemoryIsExceeded()
    {
        $process = m::mock('Symfony\Component\Process\Process')->makePartial();
        $process->shouldReceive('run')->once();
        $listener = m::mock(Listener::class)->makePartial();
        $listener->shouldReceive('memoryExceeded')->once()->with(1)->andReturn(true);
        $listener->shouldReceive('stop')->once();

        $listener->runProcess($process, 1);
    }

    public function testMakeProcessCorrectlyFormatsCommandLine()
    {
        $listener = new Listener(__DIR__);
        $options = new ListenerOptions();
        $options->delay = 1;
        $options->memory = 2;
        $options->timeout = 3;
        $process = $listener->makeProcess('connection', 'queue', $options);
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';

        $this->assertInstanceOf('Symfony\Component\Process\Process', $process);
        $this->assertEquals(__DIR__, $process->getWorkingDirectory());
        $this->assertEquals(3, $process->getTimeout());
        $this->assertEquals($escape.PHP_BINARY.$escape." console idb_queue:work {$escape}connection{$escape} --once --queue={$escape}queue{$escape} --delay=1 --memory=2 --sleep=3 --tries=0", $process->getCommandLine());
    }
}
