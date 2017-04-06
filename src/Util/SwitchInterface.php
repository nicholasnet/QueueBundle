<?php

namespace IdeasBucket\QueueBundle\Util;

use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Interface SwitchInterface
 *
 * @package IdeasBucket\QueueBundle\Util
 */
interface SwitchInterface
{
    /**
     * @param string $switchName
     *
     * @throws \InvalidArgumentException
     *
     * @return boolean
     */
    public function isOn($switchName = 'default.lock');

    /**
     * @param string $switchName
     *
     * @throws \InvalidArgumentException
     *
     * @return boolean
     */
    public function isOff($switchName = 'default.lock');

    /**
     * @param string $switchName
     *
     * @throws IOException
     * @throws \InvalidArgumentException
     */
    public function turnOn($switchName = 'default.lock');

    /**
     * @param string $switchName
     *
     * @throws IOException
     * @throws \InvalidArgumentException
     */
    public function turnOff($switchName = 'default.lock');
}