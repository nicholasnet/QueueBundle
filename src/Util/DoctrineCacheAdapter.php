<?php

namespace IdeasBucket\QueueBundle\Util;

use Doctrine\Common\Cache\CacheProvider;
use IdeasBucket\QueueBundle\Exception\InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

/**
 * Class DoctrineCacheAdapter
 *
 * @package IdeasBucket\QueueBundle\Util
 */
class DoctrineCacheAdapter implements CacheInterface
{
    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * DoctrineCacheAdapter constructor.
     *
     * @param CacheProvider $provider
     * @param string        $namespace
     * @param int           $defaultLifetime
     */
    public function __construct(CacheProvider $provider, $namespace = '', $defaultLifetime = 0)
    {
        $this->cache = $provider;
        $this->cache->setNamespace($namespace);
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        $result = $this->cache->fetch($key);

        return ($result === false) ? $default : $result;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null)
    {
        $this->cache->save($key, $value, $ttl);
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
        $this->cache->flushAll();
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        $keys = $this->convertToArray($keys);

        $this->cache->fetchMultiple($keys);
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null)
    {
        $values = $this->convertToArray($values);

        $this->cache->saveMultiple($values);
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys)
    {
        $keys = $this->convertToArray($keys);

        foreach ($keys as $key) {

            $this->cache->delete($key);
        }
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        return $this->cache->contains($key);
    }

    /**
     * @param \Traversable|array $keys
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    private function convertToArray($keys)
    {
        if ($keys instanceof \Traversable) {

            $keys = iterator_to_array($keys, false);

            return $keys;

        } elseif (!is_array($keys)) {

            throw new InvalidArgumentException(sprintf('Cache keys must be array or Traversable, "%s" given', is_object($keys) ? get_class($keys) : gettype($keys)));
        }

        return $keys;
    }


}