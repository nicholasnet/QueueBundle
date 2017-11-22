<?php

namespace IdeasBucket\QueueBundle;

use IdeasBucket\QueueBundle\Event\EventsList;
use IdeasBucket\QueueBundle\Event\JobExceptionOccurred;
use IdeasBucket\QueueBundle\Event\JobFailed;
use IdeasBucket\QueueBundle\Event\JobProcessed;
use IdeasBucket\QueueBundle\Event\JobProcessing;
use IdeasBucket\QueueBundle\Exception\ErrorHandler;
use IdeasBucket\QueueBundle\Exception\MaxAttemptsExceededException;
use IdeasBucket\QueueBundle\Job\JobsInterface;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class WorkerTest
 *
 * @package IdeasBucket\QueueBundle
 */
class WorkerTest extends TestCase
{
    public $events;
    public $exceptionHandler;

    public function setUp()
    {
        $this->events = m::spy(EventDispatcher::class);
        $this->exceptionHandler = m::spy(ErrorHandler::class);
        parent::setUp();
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testJobCanBeFired()
    {
        $worker = $this->getWorker('default', ['queue' => [$job = new WorkerFakeJob]]);
        $worker->runNextJob('default', 'queue', new WorkerOptions);
        $this->assertTrue($job->fired);
        $this->events->shouldHaveReceived('dispatch', [EventsList::JOB_PROCESSING, m::type(JobProcessing::class)])->once();
        $this->events->shouldHaveReceived('dispatch', [EventsList::JOB_PROCESSED, m::type(JobProcessed::class)])->once();

    }

    public function testJobCanBeFiredBasedOnPriority()
    {
        $worker = $this->getWorker('default', [
            'high' => [$highJob = new WorkerFakeJob, $secondHighJob = new WorkerFakeJob], 'low' => [$lowJob = new WorkerFakeJob],
        ]);
        $worker->runNextJob('default', 'high,low', new WorkerOptions);
        $this->assertTrue($highJob->fired);
        $this->assertFalse($secondHighJob->fired);
        $this->assertFalse($lowJob->fired);
        $worker->runNextJob('default', 'high,low', new WorkerOptions);
        $this->assertTrue($secondHighJob->fired);
        $this->assertFalse($lowJob->fired);
        $worker->runNextJob('default', 'high,low', new WorkerOptions);
        $this->assertTrue($lowJob->fired);
    }

    /**
     * @testdox Test exception is reported if connection throws exception on job pop
     */
    public function testExceptionIsReportedIfConnectionThrowsExceptionOnJobPop()
    {
        $worker = new InsomniacWorker(
            new WorkerFakeManager('default', new BrokenQueueConnection($e = new RuntimeException)),
            $this->events,
            $this->exceptionHandler
        );
        $worker->runNextJob('default', 'queue', $this->workerOptions());
        $this->exceptionHandler->shouldHaveReceived('report')->with($e);
    }

    public function testWorkerSleepsWhenQueueIsEmpty()
    {
        $worker = $this->getWorker('default', ['queue' => []]);
        $worker->runNextJob('default', 'queue', $this->workerOptions(['sleep' => 5]));
        $this->assertEquals(5, $worker->sleptFor);
    }

    public function testJobIsReleasedOnException()
    {
        $e = new RuntimeException;
        $job = new WorkerFakeJob(function () use ($e) {
            throw $e;
        });
        $worker = $this->getWorker('default', ['queue' => [$job]]);
        $worker->runNextJob('default', 'queue', $this->workerOptions(['delay' => 10]));
        $this->assertEquals(10, $job->releaseAfter);
        $this->assertFalse($job->deleted);
        $this->exceptionHandler->shouldHaveReceived('report')->with($e);
        $this->events->shouldHaveReceived('dispatch', [EventsList::JOB_EXCEPTION_OCCURRED, m::type(JobExceptionOccurred::class)]);
        $this->events->shouldNotHaveReceived('dispatch', [EventsList::JOB_PROCESSED, m::type(JobProcessed::class)]);
    }

    /**
     * @testdox Test job is not released if it has exceeded max attempts
     */
    public function testJobIsNotReleasedIfItHasExceededMaxAttempts()
    {
        $e = new RuntimeException;
        $job = new WorkerFakeJob(function ($job) use ($e) {

            // In normal use this would be incremented by being popped off the queue
            $job->attempts++;
            throw $e;
        });

        $job->attempts = 1;
        $worker = $this->getWorker('default', ['queue' => [$job]]);
        $worker->runNextJob('default', 'queue', $this->workerOptions(['maxTries' => 1]));
        $this->assertNull($job->releaseAfter);
        $this->assertTrue($job->deleted);
        $this->assertEquals($e, $job->failedWith);
        $this->exceptionHandler->shouldHaveReceived('report')->with($e);
        $this->events->shouldHaveReceived('dispatch', [EventsList::JOB_EXCEPTION_OCCURRED, m::type(JobExceptionOccurred::class)])->once();
        $this->events->shouldHaveReceived('dispatch', [EventsList::JOB_FAILED, m::type(JobFailed::class)])->once();
        $this->events->shouldNotHaveReceived('dispatch', [EventsList::JOB_PROCESSED, m::type(JobProcessed::class)]);
    }

    /**
     * @testdox Test job is failed if it has already exceeded max attempts
     */
    public function testJobIsFailedIfItHasAlreadyExceededMaxAttempts()
    {
        $job = new WorkerFakeJob(function ($job) {
            $job->attempts++;
        });
        $job->attempts = 2;
        $worker = $this->getWorker('default', ['queue' => [$job]]);
        $worker->runNextJob('default', 'queue', $this->workerOptions(['maxTries' => 1]));
        $this->assertNull($job->releaseAfter);
        $this->assertTrue($job->deleted);
        $this->assertInstanceOf(MaxAttemptsExceededException::class, $job->failedWith);
        $this->exceptionHandler->shouldHaveReceived('report')->with(m::type(MaxAttemptsExceededException::class));
        $this->events->shouldHaveReceived('dispatch', [EventsList::JOB_EXCEPTION_OCCURRED, m::type(JobExceptionOccurred::class)])->once();
        $this->events->shouldHaveReceived('dispatch', [EventsList::JOB_FAILED, m::type(JobFailed::class)])->once();
        $this->events->shouldNotHaveReceived('dispatch', [EventsList::JOB_PROCESSED, m::type(JobProcessed::class)]);
    }

    public function testJobBasedMaxRetries()
    {
        $job = new WorkerFakeJob(function ($job) {
            $job->attempts++;
        });
        $job->attempts = 2;
        $job->maxTries = 10;
        $worker = $this->getWorker('default', ['queue' => [$job]]);
        $worker->runNextJob('default', 'queue', $this->workerOptions(['maxTries' => 1]));
        $this->assertFalse($job->deleted);
        $this->assertNull($job->failedWith);
    }

    public function testDaemonShouldRunMaintenanceAndForce()
    {
        $manager = m::mock(Manager::class)
                    ->shouldReceive('isDownForMaintenance')
                    ->andReturn(true)
                    ->getMock();

        $eventMock = m::mock(Event::class)
                      ->shouldReceive('isPropagationStopped')
                      ->andReturn(false)
                      ->getMock();

        $dispatcher = m::mock(EventDispatcherInterface::class)
                       ->shouldReceive('dispatch')
                       ->andReturn($eventMock)
                       ->getMock();

        $exceptionHandler = m::mock(ErrorHandler::class)->makePartial();

        /** @var Worker|m\Mock $worker */
        $worker = m::mock(Worker::class, [$manager, $dispatcher, $exceptionHandler])
                   ->makePartial()
                   ->shouldReceive('isPaused')
                   ->andReturn(false)
                   ->getMock();

        $workerOptions = new WorkerOptions();

        $bool = $worker->daemonShouldRun($workerOptions);

        $this->assertFalse($bool);

        // Now test forced
        $workerOptions = new WorkerOptions();
        $workerOptions->force = true;

        $bool = $worker->daemonShouldRun($workerOptions);
        $this->assertTrue($bool);
    }

    public function testDaemonShouldRunUntilAndPaused(){
        $manager = m::mock(Manager::class)
                    ->shouldReceive('isDownForMaintenance')
                    ->andReturn(false)
                    ->getMock();

        $eventMock = m::mock(Event::class)
                      ->shouldReceive('isPropagationStopped')
                      ->andReturnValues([true, false])
                      ->getMock();

        $dispatcher = m::mock(EventDispatcherInterface::class)
                       ->shouldReceive('dispatch')
                       ->andReturn($eventMock)
                       ->getMock();

        $exceptionHandler = m::mock(ErrorHandler::class)->makePartial();

        /** @var Worker|m\Mock $worker */
        $worker = m::mock(Worker::class, [$manager, $dispatcher, $exceptionHandler])
                   ->makePartial()
                   ->shouldReceive('isPaused')
                   ->andReturn(false)
                   ->getMock();

        $workerOptions = new WorkerOptions();

        // Run once with isPropagationStopped = false
        $bool = $worker->daemonShouldRun($workerOptions);
        $this->assertFalse($bool);

        // Run with isPropagationStopped = true
        $bool = $worker->daemonShouldRun($workerOptions);
        $this->assertTrue($bool);

        $worker = m::mock(Worker::class, [$manager, $dispatcher, $exceptionHandler])
                   ->makePartial()
                   ->shouldReceive('isPaused')
                   ->andReturn(true)
                   ->getMock();

        $bool = $worker->daemonShouldRun($workerOptions);
        $this->assertFalse($bool);
    }

    /**
     * Helpers...
     */
    private function getWorker($connectionName = 'default', $jobs = [])
    {
        return new InsomniacWorker(
            ...$this->workerDependencies($connectionName, $jobs)
        );
    }

    private function workerDependencies($connectionName = 'default', $jobs = [])
    {
        return [
            new WorkerFakeManager($connectionName, new WorkerFakeConnection($jobs)),
            $this->events,
            $this->exceptionHandler,
        ];
    }

    private function workerOptions(array $overrides = [])
    {
        $options = new WorkerOptions;

        foreach ($overrides as $key => $value) {

            $options->{$key} = $value;
        }

        return $options;
    }
}

/**
 * Fakes.
 */
class InsomniacWorker extends Worker
{
    public $sleptFor;

    public function sleep($seconds)
    {
        $this->sleptFor = $seconds;
    }
}

class WorkerFakeManager extends Manager
{
    public $connections = [];

    public function __construct($name, $connection)
    {
        $this->connections[ $name ] = $connection;
    }

    public function connection($name = null)
    {
        return $this->connections[ $name ];
    }
}

class WorkerFakeConnection
{
    public $jobs = [];

    public function __construct($jobs)
    {
        $this->jobs = $jobs;
    }

    public function pop($queue)
    {
        return array_shift($this->jobs[ $queue ]);
    }
}

class BrokenQueueConnection
{
    public $exception;

    public function __construct($exception)
    {
        $this->exception = $exception;
    }

    public function pop($queue)
    {
        throw $this->exception;
    }
}

class WorkerFakeJob implements JobsInterface
{
    public $fired = false;
    public $callback;
    public $deleted = false;
    public $releaseAfter;
    public $maxTries;
    public $attempts = 0;
    public $failedWith;
    public $connectionName;

    public function __construct($callback = null)
    {
        $this->callback = $callback ?: function () {
        };
    }

    public function timeoutAt()
    {

    }

    public function fire()
    {
        $this->fired = true;
        $this->callback->__invoke($this);
    }

    public function payload()
    {
        return [];
    }

    public function maxTries()
    {
        return $this->maxTries;
    }

    public function delete()
    {
        $this->deleted = true;
    }

    public function isDeleted()
    {
        return $this->deleted;
    }

    public function release($delay = 0)
    {
        $this->releaseAfter = $delay;
    }

    public function attempts()
    {
        return $this->attempts;
    }

    public function markAsFailed()
    {
        //
    }

    public function failed($e)
    {
        $this->failedWith = $e;
    }

    public function setConnectionName($name)
    {
        $this->connectionName = $name;
    }

    public function isDeletedOrReleased()
    {

    }

    public function timeout()
    {

    }

    public function getName()
    {

    }

    public function getConnectionName()
    {

    }

    public function getQueue()
    {

    }

    public function getRawBody()
    {

    }

    public function isReleased()
    {

    }

    public function hasFailed()
    {

    }
}
