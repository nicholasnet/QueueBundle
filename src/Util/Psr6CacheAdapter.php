<?php

/*
 * Adapted from https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Cache/Simple/Psr6Cache.php
 * Part of Symfony Cache component.
 */
namespace IdeasBucket\QueueBundle\Util;

use IdeasBucket\QueueBundle\Exception\InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\CacheException as SimpleCacheException;
use Psr\Cache\CacheException as Psr6CacheException;

/**
 * Class Psr6Cache
 *
 * @package IdeasBucket\QueueBundle\Util
 */
class Psr6CacheAdapter implements CacheInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * Psr6Cache constructor.
     *
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        try {

            $item = $this->cache->getItem($key);

        } catch (SimpleCacheException $e) {

            throw $e;

        } catch (Psr6CacheException $e) {

            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null)
    {
        try {

            $item = $this->cache->getItem($key)->set($value);

        } catch (SimpleCacheException $e) {

            throw $e;

        } catch (Psr6CacheException $e) {

            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        if (null !== $ttl) {

            $item->expiresAfter($ttl);
        }

        return $this->cache->save($item);
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        try {

            return $this->cache->deleteItem($key);

        } catch (SimpleCacheException $e) {

            throw $e;

        } catch (Psr6CacheException $e) {

            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return $this->cache->clear();
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        if ($keys instanceof \Traversable) {

            $keys = iterator_to_array($keys, false);

        } elseif (!is_array($keys)) {

            throw new InvalidArgumentException(sprintf('Cache keys must be array or Traversable, "%s" given', is_object($keys) ? get_class($keys) : gettype($keys)));
        }

        try {

            $items = $this->cache->getItems($keys);

        } catch (SimpleCacheException $e) {

            throw $e;

        } catch (Psr6CacheException $e) {

            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        $values = array();

        foreach ($items as $key => $item) {

            $values[$key] = $item->isHit() ? $item->get() : $default;
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null)
    {
        $valuesIsArray = is_array($values);

        if (!$valuesIsArray && !$values instanceof \Traversable) {

            throw new InvalidArgumentException(sprintf('Cache values must be array or Traversable, "%s" given', is_object($values) ? get_class($values) : gettype($values)));
        }

        $items = array();

        try {

            if ($valuesIsArray) {

                $items = array();

                foreach ($values as $key => $value) {

                    $items[] = (string) $key;
                }

                $items = $this->cache->getItems($items);

            } else {

                foreach ($values as $key => $value) {

                    if (is_int($key)) {

                        $key = (string) $key;
                    }

                    $items[$key] = $this->cache->getItem($key)->set($value);
                }
            }

        } catch (SimpleCacheException $e) {

            throw $e;

        } catch (Psr6CacheException $e) {

            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        $ok = true;

        foreach ($items as $key => $item) {

            if ($valuesIsArray) {

                $item->set($values[$key]);
            }

            if (null !== $ttl) {

                $item->expiresAfter($ttl);
            }

            $ok = $this->cache->saveDeferred($item) && $ok;
        }
        return $this->cache->commit() && $ok;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys)
    {
        if ($keys instanceof \Traversable) {

            $keys = iterator_to_array($keys, false);

        } elseif (!is_array($keys)) {

            throw new InvalidArgumentException(sprintf('Cache keys must be array or Traversable, "%s" given', is_object($keys) ? get_class($keys) : gettype($keys)));
        }

        try {

            return $this->cache->deleteItems($keys);

        } catch (SimpleCacheException $e) {

            throw $e;

        } catch (Psr6CacheException $e) {

            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        try {

            return $this->cache->hasItem($key);

        } catch (SimpleCacheException $e) {

            throw $e;

        } catch (Psr6CacheException $e) {

            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
