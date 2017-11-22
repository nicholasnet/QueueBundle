<?php

namespace IdeasBucket\QueueBundle\Util;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;


class CacheAdapterTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testGetter()
    {
        $cacheMock = m::mock(CacheInterface::class)
                      ->shouldReceive('get')
                      ->once()
                      ->andReturn('test')
                      ->getMock();

        $class = new CacheAdapter($cacheMock);


        $this->assertEquals('test', $class->get('test'));
    }

}