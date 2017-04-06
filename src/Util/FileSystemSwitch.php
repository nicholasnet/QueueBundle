<?php

namespace IdeasBucket\QueueBundle\Util;

use IdeasBucket\Common\Utils\StringHelper;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class FileBasedSystemSwitch
 *
 * @package IdeasBucket\QueueBundle\Util
 */
class FileSystemSwitch implements SwitchInterface
{
    /**
     * Path where lock will be stored.
     *
     * @var string
     */
    private $lockStoragePath;

    /**
     * FileBasedSystemSwitch constructor.
     *
     * @param $lockStoragePath
     */
    public function __construct($lockStoragePath)
    {
        if (!is_dir($lockStoragePath)) {

            $fs = new Filesystem();
            $fs->mkdir($lockStoragePath);
        }

        if (!is_writable($lockStoragePath)) {

            throw new IOException(sprintf('The directory "%s" is not writable.', $lockStoragePath), 0, null, $lockStoragePath);
        }

        $this->lockStoragePath = $lockStoragePath;
    }

    /**
     * @inheritDoc
     */
    public function isOn($switchName = 'default.lock')
    {
        $this->validateLockName($switchName);

        return file_exists($this->lockStoragePath . DIRECTORY_SEPARATOR . $switchName);
    }

    /**
     * @inheritDoc
     */
    public function isOff($switchName = 'default.lock')
    {
        return (false === $this->isOn($switchName));
    }

    /**
     * @inheritDoc
     */
    public function turnOn($switchName = 'default.lock')
    {
        $this->validateLockName($switchName);
        file_put_contents($this->lockStoragePath . DIRECTORY_SEPARATOR . $switchName, date('Y-m-d H:i:s e'));
    }

    /**
     * @inheritDoc
     */
    public function turnOff($switchName = 'default.lock')
    {
        $this->validateLockName($switchName);
        @unlink($this->lockStoragePath . DIRECTORY_SEPARATOR . $switchName);
    }

    /**
     * This magic function allows user to check and establish lock dynamically.
     * For example:
     *
     * $lock->isWorkerOn(); Will translate to $lock->isOn('worker.lock');
     * $lock->isWorkerOff(); Will translate to $lock->isOff('worker.lock');
     * $lock->turnWorkerOff(); Will translate to $lock->turnOn('worker.lock');
     * $lock->turnWorkerOff(); Will translate to $lock->turnOff('worker.lock');
     */
    public function __call($name, $arguments)
    {
        $re = '/(?P<action>turn|is)(?P<lock_name>[A-Za-z]+)(?P<flag>Off|On)/';
        preg_match($re, $name, $matches);

        if (empty($matches['action']) || empty($matches['lock_name']) || empty($matches['flag'])) {

            throw new \BadMethodCallException('Invalid method');
        }

        $lockName = StringHelper::slug($matches['lock_name']) . '.lock';
        $method = $matches['action'] . $matches['flag'];

        return $this->{$method}($lockName);
    }

    /**
     * @param $lockName
     *
     * @return boolean
     */
    protected function validateLockName($lockName)
    {
        if (empty($lockName)) {

            throw new \InvalidArgumentException('Invalid lock name');
        }

        if (preg_match('/^[a-z][a-z\-]+\.lock$/', $lockName)) {

            return true;
        }

        throw new \InvalidArgumentException('Invalid lock name');
    }
}