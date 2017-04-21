# QueueBundle

[![Build Status](https://travis-ci.org/nicholasnet/QueueBundle.svg?branch=master)](https://travis-ci.org/nicholasnet/QueueBundle)

- [Introduction](#introduction)
    - [Installation](#installation)
    - [Configuration](#configuration)
    - [Connections Vs. Queues](#connections-vs-queues)
    - [Driver Prerequisites](#driver-prerequisites)
- [Creating Jobs](#creating-jobs)
    - [Generating Job Classes](#generating-job-classes)
    - [Class Structure](#class-structure)
- [Dispatching Jobs](#dispatching-jobs)
    - [Delayed Dispatching](#delayed-dispatching)
    - [Customizing The Queue & Connection](#customizing-the-queue-and-connection)
    - [Specifying Max Job Attempts / Timeout Values](#max-job-attempts-and-timeout)
    - [Error Handling](#error-handling)
- [Running The Queue Worker](#running-the-queue-worker)
    - [Queue Priorities](#queue-priorities)
    - [Queue Workers & Deployment](#queue-workers-and-deployment)
    - [Job Expirations & Timeouts](#job-expirations-and-timeouts)
- [Supervisor Configuration](#supervisor-configuration)
- [Dealing With Failed Jobs](#dealing-with-failed-jobs)
    - [Cleaning Up After Failed Jobs](#cleaning-up-after-failed-jobs)
    - [Retrying Failed Jobs](#retrying-failed-jobs)
- [Job Events](#job-events)
- [Other Database and library support](#database-other-support)
- [GIST](#gist)

<a name="introduction"></a>
## Introduction

**This QueueBundle is heavily inspired by __Laravel Queue__ package. In fact some of the file are directly copied over. So, hats off to __Taylor Otwell__ and __Laravel team__ for providing an awesome package for the community.**

<a name="installation"></a>
### Installation

You can install QueueBundle by composer

    composer require ideasbucket/queue-bundle
    
QueueBundle supports Symfony 2.8, 3.0 and above.

<a name="configuration"></a>
### Configuration

Once you install the bundle you will need to make change in your `AppKernel.php` by adding the bundle class entry like this.

    <?php
    
    use Symfony\Component\HttpKernel\Kernel;
    use Symfony\Component\Config\Loader\LoaderInterface;

    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            $bundles = array(
                new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
                new Symfony\Bundle\SecurityBundle\SecurityBundle(),
                new Symfony\Bundle\TwigBundle\TwigBundle(),
                new Symfony\Bundle\MonologBundle\MonologBundle(),
                new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
                new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
                new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
                new IdeasBucket\QueueBundle\IdeasBucketQueueBundle(), // ADD THIS
                new AppBundle\AppBundle(),
            );

            ....

            return $bundles;
        }
    }

Then in `config.yml` you can define configurations for each of the queue drivers that are included with the bundle, which includes a database, [Beanstalkd](https://kr.github.io/beanstalkd/), [Amazon SQS](https://aws.amazon.com/sqs/), [Redis](http://redis.io),  and a synchronous driver that will execute jobs immediately (for local use). A `null` queue driver is also included which simply discards queued jobs.
    
Basic minimal configuration that is needed for QueueBundle is to configure `cache_handler`. Basically it can be any service that implements any one of these interfaces.
     
- PSR-16 Cache
- PSR-6 Cache Pool
- Doctrine Cache

For cache handler you can define service like this.

    # In service.yml or config.yml file
    app.cache:
        app: ANY_CACHE_ADAPTER
        
If possible we recommend PSR-16 cache interface.        

You can use any cache adapter here. For more information regarding cache handler please visit [here](http://symfony.com/blog/new-in-symfony-3-1-cache-component) or [here](http://stackoverflow.com/questions/8893081/how-to-cache-in-symfony-2) . 

Full configuration for QueueBundle is following.     
          
    ideasbucket_queue:
        cache_handler: app.cache
        default: sync # default
        # Default config for command path you may need to change 
        # this if you are using Symfony 2.x directory structure.
        command_path: %kernel.root_dir%/../bin/
        lock_path: ~
        lock_service: ideasbucket_queue.filesystem_switch # Default value
        connections:
            sqs:
                driver: sqs
                key: YOUR_KEY
                secret: YOUR_SECRET
                prefix: https://sqs.us-west-2.amazonaws.com/some-id
                queue: default
                region: us-west-2
            redis:
                driver: redis
                client: YOUR_PREDIS_CLIENT
                queue: default
                retry_after: 90
            beanstalkd:
                driver: beanstalkd
                host: localhost
                port: 11300
                persistent: ~
                queue: default
                retry_after: 90
            database:
                driver: database
                queue: default
                repository: YOUR_QUEUE_REPOSITORY
                retry_after: 90
        # If you want to store failed jobs in database.          
        #failed_job_repository: FAILED_JOB_REPOSITORY

<a name="connections-vs-queues"></a>
### Connections vs. Queues

Before getting started with QueueBundle, it is important to understand the distinction between "connections" and "queues". In your `config.yml` you can define configuration for `connections`. This option defines a particular connection to a backend service such as Amazon SQS, Beanstalk, or Redis. However, any given queue connection may have multiple "queues" which may be thought of as different stacks or piles of queued jobs.

Note that each connection configuration example in the `config.yml` configuration file contains a `queue` attribute. This is the default queue that jobs will be dispatched to when they are sent to a given connection. In other words, if you dispatch a job without explicitly defining which queue it should be dispatched to, the job will be placed on the queue that is defined in the `queue` attribute of the connection configuration:

    // This job is sent to the default queue...
    $this->get('idb_queue')->push('service_id');

    // This job is sent to the "emails" queue...
    $this->get('idb_queue')->push('service_id', [], 'emails');

Some applications may not need to ever push jobs onto multiple queues, instead preferring to have one simple queue. However, pushing jobs to multiple queues can be especially useful for applications that wish to prioritize or segment how jobs are processed, since the QueueBundle queue worker allows you to specify which queues it should process by priority. For example, if you push jobs to a `high` queue, you may run a worker that gives them higher processing priority:

    php console idb_queue:work --queue=high,default

<a name="driver-prerequisites"></a>
### Driver Prerequisites

#### Database

In order to use the `database` queue driver, you will need a run a following command which will generate necessary repository and entity to support the queue:

    php console idb_queue:database
    
This will generate the necessary files in your `cache/output` folder, which you will need to move to appropriate location. Then define a service which definition will be shown during end of the command run.
     
Command assumes that you are running Doctrine (ORM or ODM) with annotation config. If you are using any other configuration format then you will have to make necessary adjustment in generated code. 

Furthermore if you want to use **relational database** then you will need `"doctrine/orm"` if you want to use **MongoDB** then you will need `"doctrine/mongodb-odm"` and `"doctrine/mongodb-odm-bundle"`. 

If for any reason you need support for any other library besides Doctrine or any other database then please see [here](#database-other-support)    

#### Redis

In order to use the `redis` queue driver, you will need to have any service that provided `Predis` client instance. If you are using SNC RedisBundle then it will be `snc_redis.default_client` considering that you are using Predis as default Redis client.

#### Other Driver Prerequisites

The following dependencies are needed for the listed queue drivers:

- Amazon SQS: `aws/aws-sdk-php ~3.0`

- Beanstalkd: `pda/pheanstalk ~3.0`

- Redis: `predis/predis ~1.0`


<a name="creating-jobs"></a>
## Creating Jobs

<a name="generating-job-classes"></a>
### Generating Job Classes

Every Job is basically a service that implements `IdeasBucket\QueueBundle\QueueableInterface`. QueueBundle provides command for generating a job.

    php console idb_queue:create_job

The generated class will be inside `Job` folder inside the bundle that you chose during command.

<a name="class-structure"></a>
### Job Structure

Job classes are very simple, it simply implements Queueable interface containing only a `fire` method which is called when the job is processed by the queue. To get started, let's take a look at an example job class. In this example, we'll send email using queue:

    <?php

    namespace AppBundle\Job;

    use IdeasBucket\QueueBundle\QueueableInterface;
    use IdeasBucket\QueueBundle\Job\JobsInterface;
    
    class QueueMailer implements QueueableInterface
    {
        /**
         * @var \Swift_Mailer
         */
        private $mailer;
        
        public function __construct(\Swift_Mailer $mailer)
        {
            $this->mailer = $mailer;
        }
        
        /**
         * @param JobsInterface $job
         * @param array         $data
         */
        public function fire(JobsInterface $job, array $data = null)
        {
            // Create a message
            //....
            $this->mailer->send($message);
            
            $job->delete();
        }
    }

Then you will need to define a service for the job in your service.yml file.

    services:
        app_queue_mailer:
            class: AppBundle\Job\QueueMailer
            arguments: [ '@mailer']
            
> {note} Binary data, such as raw image contents, should be passed through the `base64_encode` function before being passed to a queued job. Otherwise, the job may not properly serialize to JSON when being placed on the queue.

<a name="dispatching-jobs"></a>
## Pushing/Dispatching Jobs

Once you have written your job class and configured the service for it, you may dispatch it using the `idb_queue` service.:

    <?php

    namespace AppBundle\Controllers;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    
    class MailerController extends Controller
    {
        /**
         * The action/method handles the user registration.
         *
         * @param Request $request
         * @Route("/mail", name="mail")
         * @Method({"GET", "POST"})
         *
         * @return Response
         */
        public function mail(Request $request)
        {
            $this->get('idb_queue')->push('app_queue_mailer', ['message' => 'Hello World']);
            
            // Rest of the code......
        }
    }

<a name="delayed-dispatching"></a>
### Delayed Push/Dispatching

If you would like to delay the execution of a queued job.

    $tenMinutesLater = (new \DateTime)->modify('10 minute');
    $this->get('idb_queue')->later($tenMinutesLater, 'app_queue_mailer', $data);

> {note} The Amazon SQS queue service has a maximum delay time of 15 minutes.

<a name="customizing-the-queue-and-connection"></a>
### Customizing The Queue & Connection

#### Dispatching To A Particular Queue

By pushing jobs to different queues, you may "categorize" your queued jobs and even prioritize how many workers you assign to various queues. Keep in mind, this does not push jobs to different queue "connections" as defined by your queue configuration file, but only to specific queues within a single connection:

    $this->get('idb_queue')->push('app_queue_mailer', ['message' => 'Hello World'], 'processing');

#### Dispatching To A Particular Connection

If you are working with multiple queue connections, you may specify which connection to push a job to:

    // On processing and sqs connection
    $this->get('idb_queue')->push('app_queue_mailer', ['message' => 'Hello World'], 'processing', 'sqs');


<a name="max-job-attempts-and-timeout"></a>
### Specifying Max Job Attempts / Timeout Values

#### Max Attempts

One approach to specifying the maximum number of times a job may be attempted is via the `--tries` switch on the Console command line:

    php console idb_queue:work --tries=3

However, you may take a more granular approach by defining the maximum number of attempts on the job class itself. If the maximum number of attempts is specified on the job, it will take precedence over the value provided on the command line:

    <?php

    namespace AppBundle\Job;
    
    use IdeasBucket\QueueBundle\QueueableInterface;
    use IdeasBucket\QueueBundle\Job\JobsInterface;
        
    class QueueMailer implements QueueableInterface
    {
        /**
         * The number of max times the job may be attempted.
         *
         * @var int
         */
        public $maxTries = 5;
        
        /**
         * @param JobsInterface $job
         * @param mixed         $data
         */
        public function fire(JobsInterface $job, $data = null)
        {
            // ....
                         
            $job->delete();
        }
    }
    
If you don't like to use public property then.
    
    namespace AppBundle\Job;
        
    use IdeasBucket\QueueBundle\QueueableInterface;
    use IdeasBucket\QueueBundle\Job\JobsInterface;
            
    class QueueMailer implements QueueableInterface
    {
        /**
         * The number of max times the job may be attempted.
         *
         * @var int
         */
         private $maxTries = 5;
         
         public function getMaxTries()
         {
             return $this->maxTries;
         }
         
         /**
          * @param JobsInterface $job
          * @param mixed         $data
          */
         public function fire(JobsInterface $job, $data = null)
         {
             // ....
             
             $job->delete();
         }
    }

#### Timeout

Likewise, the maximum number of seconds that jobs can run may be specified using the `--timeout` switch on the Artisan command line:

    php console idb_queue:work --timeout=30

However, you may also define the maximum number of seconds a job should be allowed to run on the job class itself. If the timeout is specified on the job, it will take precedence over any timeout specified on the command line:

    <?php

    namespace AppBundle\Job;
    
    use IdeasBucket\QueueBundle\QueueableInterface;
    use IdeasBucket\QueueBundle\Job\JobsInterface;
        
    class QueueMailer implements QueueableInterface
    {
        /**
         * The number of max times the job may be attempted.
         *
         * @var int
         */
        public $timeout = 5;
        
        /**
         * @param JobsInterface $job
         * @param mixed         $data
         */
        public function fire(JobsInterface $job, $data = null)
        {
            // ....
                         
            $job->delete();
        }
    }
    
If you don't like to use public property then.
    
    namespace AppBundle\Job;
        
    use IdeasBucket\QueueBundle\QueueableInterface;
    use IdeasBucket\QueueBundle\Job\JobsInterface;
            
    class QueueMailer implements QueueableInterface
    {
        /**
         * The number of max times the job may be attempted.
         *
         * @var int
         */
         private $timeout = 5;
         
         public function getTimeout()
         {
             return $this->timeout;
         }
         
         /**
          * @param JobsInterface $job
          * @param mixed         $data
          */
         public function fire(JobsInterface $job, $data = null)
         {
             // ....
             
             $job->delete();
         }
    }

<a name="error-handling"></a>
### Error Handling

If an exception is thrown while the job is being processed, the job will automatically be released back onto the queue so it may be attempted again. The job will continue to be released until it has been attempted the maximum number of times allowed by your application. The maximum number of attempts is defined by the `--tries` switch used on the `idb_queue:work` console command. Alternatively, the maximum number of attempts may be defined on the job class itself. More information on running the queue worker [can be found below](#running-the-queue-worker).

<a name="running-the-queue-worker"></a>
## Running The Queue Worker

QueueBundle includes a queue worker that will process new jobs as they are pushed onto the queue. You may run the worker using the `idb_queue:work` console command. Note that once the `idb_queue:work` command has started, it will continue to run until it is manually stopped or you close your terminal:

    php console idb_queue:work
    
You can also use
    
    php console idb_queue:listen    

> {tip} To keep the `idb_queue:work` process running permanently in the background, you should use a process monitor such as [Supervisor](#supervisor-configuration) to ensure that the queue worker does not stop running.

Remember, queue workers are long-lived processes and store the booted application state in memory. As a result, they will not notice changes in your code base after they have been started. So, during your deployment process, be sure to [restart your queue workers](#queue-workers-and-deployment).

#### Specifying The Connection & Queue

You may also specify which queue connection the worker should utilize. The connection name passed to the `work` command should correspond to one of the connections defined in your `config/queue.php` configuration file:

    php console idb_queue:work redis

You may customize your queue worker even further by only processing particular queues for a given connection. For example, if all of your emails are processed in an `emails` queue on your `redis` queue connection, you may issue the following command to start a worker that only processes only that queue:

    php console idb_queue:work redis --queue=emails

#### Resource Considerations

Daemon queue workers do not "reboot" the framework before processing each job. Therefore, you should free any heavy resources after each job completes. For example, if you are doing image manipulation with the GD library, you should free the memory with `imagedestroy` when you are done.

<a name="queue-priorities"></a>
### Queue Priorities

Sometimes you may wish to prioritize how your queues are processed. For example, in your `config.yml` you may set the default `queue` for your `redis` connection to `low`. However, occasionally you may wish to push a job to a `high` priority queue like so:

    $this->get('idb_queue')->push('app_queue_mailer', ['message' => 'Hello World'], 'high');

To start a worker that verifies that all of the `high` queue jobs are processed before continuing to any jobs on the `low` queue, pass a comma-delimited list of queue names to the `work` command:

    php console idb_queue:work --queue=high,low

<a name="queue-workers-and-deployment"></a>
### Queue Workers & Deployment

Since queue workers are long-lived processes, they will not pick up changes to your code without being restarted. So, the simplest way to deploy an application using queue workers is to restart the workers during your deployment process. You may gracefully restart all of the workers by issuing the `idb_queue:restart` command:

    php console idb_queue:restart

This command will instruct all queue workers to gracefully "die" after they finish processing their current job so that no existing jobs are lost. Since the queue workers will die when the `idb_queue:restart` command is executed, you should be running a process manager such as [Supervisor](#supervisor-configuration) to automatically restart the queue workers.

<a name="job-expirations-and-timeouts"></a>
### Job Expirations & Timeouts

#### Job Expiration

In your `config.yml` configuration file, each queue connection defines a `retry_after` option. This option specifies how many seconds the queue connection should wait before retrying a job that is being processed. For example, if the value of `retry_after` is set to `90`, the job will be released back onto the queue if it has been processing for 90 seconds without being deleted. Typically, you should set the `retry_after` value to the maximum number of seconds your jobs should reasonably take to complete processing.

> {note} The only queue connection which does not contain a `retry_after` value is Amazon SQS. SQS will retry the job based on the [Default Visibility Timeout](https://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/AboutVT.html) which is managed within the AWS console.

#### Worker Timeouts

The `idb_queue:work` console command exposes a `--timeout` option. The `--timeout` option specifies how long the queue master process will wait before killing off a child queue worker that is processing a job. Sometimes a child queue process can become "frozen" for various reasons, such as an external HTTP call that is not responding. The `--timeout` option removes frozen processes that have exceeded that specified time limit:

    php console idb_queue:work --timeout=60

The `retry_after` configuration option and the `--timeout` CLI option are different, but work together to ensure that jobs are not lost and that jobs are only successfully processed once.

> {note} The `--timeout` value should always be at least several seconds shorter than your `retry_after` configuration value. This will ensure that a worker processing a given job is always killed before the job is retried. If your `--timeout` option is longer than your `retry_after` configuration value, your jobs may be processed twice.

#### Worker Sleep Duration

When jobs are available on the queue, the worker will keep processing jobs with no delay in between them. However, the `sleep` option determines how long the worker will "sleep" if there are no new jobs available:

    php console idb_queue:work --sleep=3

<a name="supervisor-configuration"></a>
## Supervisor Configuration

#### Installing Supervisor

Supervisor is a process monitor for the Linux operating system, and will automatically restart your `idb_queue:work` process if it fails. To install Supervisor on Ubuntu, you may use the following command:

    sudo apt-get install supervisor

#### Configuring Supervisor

Supervisor configuration files are typically stored in the `/etc/supervisor/conf.d` directory. Within this directory, you may create any number of configuration files that instruct supervisor how your processes should be monitored. For example, let's create a `queue-worker.conf` file that starts and monitors a `idb_queue:work` process:

    [program:queue-worker]
    process_name=%(program_name)s_%(process_num)02d
    command=php /home/project/acme/bin/console idb_queue:work sqs --sleep=3 --tries=3
    autostart=true
    autorestart=true
    user=johndoe
    numprocs=8
    redirect_stderr=true
    stdout_logfile=/home/project/acme/var/log/worker.log

In this example, the `numprocs` directive will instruct Supervisor to run 8 `idb_queue:work` processes and monitor all of them, automatically restarting them if they fail. Of course, you should change the `idb_queue:work sqs` portion of the `command` directive to reflect your desired queue connection.

#### Starting Supervisor

Once the configuration file has been created, you may update the Supervisor configuration and start the processes using the following commands:

    sudo supervisorctl reread

    sudo supervisorctl update

    sudo supervisorctl start queue-worker:*

For more information on Supervisor, consult the [Supervisor documentation](http://supervisord.org/index.html).

<a name="dealing-with-failed-jobs"></a>
## Dealing With Failed Jobs

Sometimes your queued jobs will fail. Don't worry, things don't always go as planned! QueueBundle includes a convenient way to specify the maximum number of times a job should be attempted. After a job has exceeded this amount of attempts, it can be inserted into the database. To use database to store the failed job, you should use the `idb_queue:fail_database` command:

    php console idb_queue:fail_database
 
This will command will create ask you few questions that you will have to answer. Then necessary file will be generated for you. Process is similar as using database for queue.    

Then, when running your [queue worker](#running-the-queue-worker), you should specify the maximum number of times a job should be attempted using the `--tries` switch on the `idb_queue:work` command. If you do not specify a value for the `--tries` option, jobs will be attempted indefinitely:

    php console idb_queue:work redis --tries=3

<a name="cleaning-up-after-failed-jobs"></a>
### Cleaning Up After Failed Jobs

You will need to implement a `IdeasBucket\QueueBundle\QueueErrorInterface`  directly on your job class which will allow you to perform job specific clean-up when a failure occurs. This is the perfect location to send an alert to your users or revert any actions performed by the job. The `Exception` that caused the job to fail will be passed to the `failed` method:

    <?php

    namespace AppBundle\Job;
        
    use IdeasBucket\QueueBundle\QueueableInterface;
    use IdeasBucket\QueueBundle\Job\JobsInterface;
    use IdeasBucket\QueueBundle\QueueErrorInterface;
            
    class QueueMailer implements QueueableInterface, QueueErrorInterface
    {
        public function fire(JobsInterface $job, array $data = null)
        {
            // ....
             
            $job->delete();
        }
        
        public function failed(\Exception $e, $payload = null)
        {
           // Do something with the error
        }
    }
<a name="retrying-failed-jobs"></a>
### Retrying Failed Jobs

To view all of your failed jobs that have been inserted into your database, you may use the `idb_queue:failed` Console command:

    php console idb_queue:failed

The `idb_queue:failed` command will list the job ID, connection, queue, and failure time. The job ID may be used to retry the failed job. For instance, to retry a failed job that has an ID of `5`, issue the following command:

    php console idb_queue:retry 5

To retry all of your failed jobs, execute the `idb_queue:retry` command and pass `all` as the ID:

    php console idb_queue:retry all

If you would like to delete a failed job, you may use the `idbb_queue:forget` command:

    php console idb_queue:forget 5

To delete all of your failed jobs, you may use the `idb_queue:flush` command:

    php console idb_queue:flush

<a name="job-events"></a>
### Job Events

QueueBundle provides several Events that you can listen to during Job execution. This event is a great opportunity to notify your team via email or HipChat or just a way to take some actions if job fails. You can attach the listeners like this.

    <?php
    
    namespace AppBundle\EventListener;
    
    use Symfony\Component\EventDispatcher\Event;
    
    /**
     * Class QueueListener
     *
     * @package AppBundle\EventListener
     */
    class QueueListener
    {
        /**
         * This method will be called before Job firing
         *
         * @param Event $event
         */
        public function during(Event $event)
        {
            
        }
    }
    
Then register a service
    
    appbundle.queue_listener:
    class: AppBundle\EventListener\QueueListener
    tags:
        - { name: kernel.event_listener, event: job_processing, method: during }
        - { name: kernel.event_listener, event: job_failed, method: during }
        - { name: kernel.event_listener, event: job_exception_occurred, method: during }
        - { name: kernel.event_listener, event: job_processed, method: during }
        - { name: kernel.event_listener, event: looping, method: during }
        - { name: kernel.event_listener, event: worker_stopping, method: during }
        
Of course, you can call different methods during different events. If you are using Symfony `3.2 or above` we recommend you to use class constant instead like this.        

    appbundle.queue_listener:
    class: AppBundle\EventListener\QueueListener
    tags:
        - { name: kernel.event_listener, event: !php/const:IdeasBucket\QueueBundle\Event\EventList::JOB_EXCEPTION_OCCURRED, method: during }
        - { name: kernel.event_listener, event: !php/const:IdeasBucket\QueueBundle\Event\EventList::JOB_FAILED, method: during }
        - { name: kernel.event_listener, event: !php/const:IdeasBucket\QueueBundle\Event\EventList::JOB_PROCESSED, method: during }
        - { name: kernel.event_listener, event: !php/const:IdeasBucket\QueueBundle\Event\EventList::JOB_PROCESSING, method: during }
        - { name: kernel.event_listener, event: !php/const:IdeasBucket\QueueBundle\Event\EventList::LOOPING, method: during }
        - { name: kernel.event_listener, event: !php/const:IdeasBucket\QueueBundle\Event\EventList::WORKER_STOPPING, method: during }
        
For more information with regards to create event listeners and registering please visit [Symfony Documentation](http://symfony.com/doc/current/event_dispatcher/before_after_filters.html#creating-an-event-listener).    


<a name="database-other-support"></a>
### Other Database and library support

When you execute command `idb_queue:database` it assumes that you are using Doctrine (ORM or ODM). If for any reason you are not using Doctrine or just want to support other databases then in QueueBundle it is fairly straight forward.

All you need to make sure is that Repository implements interface `IdeasBucket\QueueBundle\Repository\DatabaseQueueRepositoryInterface` and Entity implements `IdeasBucket\QueueBundle\Entity\DatabaseQueueEntityInterface`. As long as these requirements are satisfied you can use any library or database.

<a name="gist"></a>
### GIST

Coming soon.
