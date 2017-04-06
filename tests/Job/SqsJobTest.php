<?php

namespace IdeasBucket\QueueBundle\Job;

use Aws\Sqs\SqsClient;
use IdeasBucket\QueueBundle\QueueableInterface;
use IdeasBucket\QueueBundle\Type\SqsQueue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mockery as m;

class SqsJobTest extends TestCase
{
    private $mockedSqsClient;
    private $mockedJobData;
    private $mockedPayload;
    private $mockedJob = 'foo';
    private $mockedData = ['data'];
    private $mockedReceiptHandle = '0NNAq8PwvXuWv5gMtS9DJ8qEdyiUwbAjpp45w2m6M4SJ1Y+PxCh7R930NRB8ylSacEmoSnW18bgd4nK\/O6ctE+VFVul4eD23mA07vVoSnPI4F\/voI1eNCp6Iax0ktGmhlNVzBwaZHEr91BRtqTRM3QKd2ASF8u+IQaSwyl\/DGK+P1+dqUOodvOVtExJwdyDLy1glZVgm85Yw9Jf5yZEEErqRwzYz\/qSigdvW4sm2l7e4phRol\/+IjMtovOyH\/ukueYdlVbQ4OshQLENhUKe7RNN5i6bE\/e5x9bnPhfj2gbM';
    private $mockedMessageId = 'e3cd03ee-59a3-4ad8-b0aa-ee2e3808ac81';
    private $account = '1234567891011';
    private $queueName = 'emails';
    private $baseUrl = 'https://sqs.someregion.amazonaws.com';
    private $queueUrl;
    private $releaseDelay = 0;

    public function setUp()
    {
        $this->mockedSqsClient = $this->getMockBuilder(SqsClient::class)
                                      ->setMethods(['deleteMessage'])
                                      ->disableOriginalConstructor()
                                      ->getMock();

        $this->mockedPayload = json_encode(['job' => $this->mockedJob, 'data' => $this->mockedData, 'attempts' => 1]);
        $this->queueUrl = $this->baseUrl . '/' . $this->account . '/' . $this->queueName;

        $this->mockedJobData = [
            'Body'          => $this->mockedPayload,
            'MD5OfBody'     => md5($this->mockedPayload),
            'ReceiptHandle' => $this->mockedReceiptHandle,
            'MessageId'     => $this->mockedMessageId,
            'Attributes'    => ['ApproximateReceiveCount' => 1],
        ];
    }

    public function tearDown()
    {
        m::close();
    }

    public function testFireProperlyCallsTheJobHandler()
    {
        $handler = m::mock(QueueableInterface::class);
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')->with('foo')->andReturn(true);
        $container->shouldReceive('get')->with('foo')->andReturn($handler);
        $job = new SqsJob($container, $this->mockedSqsClient, $this->mockedJobData, 'something', $this->queueUrl);
        $handler->shouldReceive('fire')->withArgs([$job, ['data']]);
        $job->fire();
    }

    public function testDeleteRemovesTheJobFromSqs()
    {
        $container = m::mock(ContainerInterface::class);
        $queue = $this->getMockBuilder(SqsQueue::class)->setMethods(['getQueue'])->setConstructorArgs([$this->mockedSqsClient, $this->queueName, $this->account])->getMock();
        $queue->setContainer($container);
        $job = new SqsJob($container, $this->mockedSqsClient, $this->mockedJobData, 'something', $this->queueUrl);
        $job->getSqs()->expects($this->once())->method('deleteMessage')->with(['QueueUrl' => $this->queueUrl, 'ReceiptHandle' => $this->mockedReceiptHandle]);
        $job->delete();
    }

    public function testReleaseProperlyReleasesTheJobOntoSqs()
    {
        $mockedSqsClient = $this->getMockBuilder(SqsClient::class)
                                ->setMethods(['changeMessageVisibility'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $container = m::mock(ContainerInterface::class);
        $queue = $this->getMockBuilder(SqsQueue::class)->setMethods(['getQueue'])->setConstructorArgs([$this->mockedSqsClient, $this->queueName, $this->account])->getMock();
        $queue->setContainer($container);
        $job = new SqsJob($container, $mockedSqsClient, $this->mockedJobData, 'something', $this->queueUrl);
        $job->getSqs()->expects($this->once())->method('changeMessageVisibility')->with(['QueueUrl' => $this->queueUrl, 'ReceiptHandle' => $this->mockedReceiptHandle, 'VisibilityTimeout' => $this->releaseDelay]);
        $job->release($this->releaseDelay);
        $this->assertTrue($job->isReleased());
    }
}
