<?php

namespace IdeasBucket\QueueBundle\Exception;

use Psr\Cache\InvalidArgumentException as Psr6CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInterface;

/**
 * Class InvalidArgumentException
 *
 * @package IdeasBucket\QueueBundle\Exception
 */
class InvalidArgumentException extends \InvalidArgumentException implements Psr6CacheInterface, SimpleCacheInterface
{
}