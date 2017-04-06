<?php

namespace IdeasBucket\QueueBundle\Util;

use Doctrine\Common\Cache\CacheProvider;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;


/**
 * Class CacheAdapter for converting cache library PSR-16 compatible.
 *
 * @package IdeasBucket\QueueBundle\Util
 */
class CacheAdapter implements CacheInterface
{
    /**
     * Class that is being adapted.
     *
     * @var CacheInterface|CacheItemPoolInterface|CacheProvider
     */
    protected $cache;

    /**
     * CacheAdapter constructor.
     *
     * @param $cache
     */
    public function __construct($cache)
    {
        if ($cache instanceof CacheInterface) { // PSR 16 implementation no adapter necessary.

            $this->cache = $cache;

        } elseif ($cache instanceof CacheItemPoolInterface) { // PSR 6

            $this->cache = new Psr6CacheAdapter($cache);

        } elseif ($cache instanceof CacheProvider) { // Doctrine cache

            $this->cache = new DoctrineCacheAdapter($cache);

        } else {

            throw new \InvalidArgumentException('Can only use Cache that is either Doctrine/Common/Cache, PSR6 CacheItemPoolInterface or PSR16 CacheInterface.');
        }
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        $this->cache->get($key, $default);
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null)
    {
        $this->cache->set($key, $value, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        $this->cache->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->cache->clear();
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        $this->cache->getMultiple($keys, $default);
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null)
    {
        $this->cache->setMultiple($values, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys)
    {
        $this->cache->deleteMultiple($keys);
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        $this->cache->has($key);
    }
}
