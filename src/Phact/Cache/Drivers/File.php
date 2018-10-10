<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/02/17 12:32
 */

namespace Phact\Cache\Drivers;

use Phact\Cache\CacheDriver;
use Phact\Components\PathInterface;
use Phact\Helpers\Paths;

class File extends CacheDriver
{
    /**
     * Path for store cache files
     *
     * @var string
     */
    public $path = 'runtime.cache';

    /**
     * Extension of cache files
     *
     * @var string
     */
    public $extension = '.cache';

    /**
     * Clean probability items from 1 million
     *
     * @var int
     */
    public $gcProbability = 10;

    /**
     * Directory levels for cache files
     *
     * @var int
     */
    public $directoryLevel = 0;

    /**
     * File mode
     *
     * @var int
     */
    public $mode = 0755;

    /**
     * Base path
     *
     * @var string
     */
    protected $_basePath;

    /**
     * @var PathInterface
     */
    protected $_pathHandler;

    public function __construct(PathInterface $pathHandler = null)
    {
        $this->_pathHandler = $pathHandler;
    }

    public function has($key)
    {
        $filePath = $this->getFileName($key);
        return is_file($filePath) && @filemtime($filePath) > time();
    }

    public function delete($key)
    {
        $unlink = true;
        if ($this->has($key)) {
            $unlink = @unlink($this->getFileName($key));
        }
        return $unlink;
    }

    public function clear()
    {
        $this->gcRecursive($this->getBasePath(), false);
        return true;
    }

    /**
     * Read cache value from file
     *
     * @param $key
     * @return bool|null|string
     */
    protected function getValue($key)
    {
        if ($this->has($key)) {
            $filePath = $this->getFileName($key);
            $fp = @fopen($filePath, 'r');
            if ($fp !== false) {
                @flock($fp, LOCK_SH);
                $cacheValue = @stream_get_contents($fp);
                @flock($fp, LOCK_UN);
                @fclose($fp);
                return $cacheValue;
            }
        }
        return null;
    }

    /**
     * Store cache value to disk
     *
     * @param $key
     * @param $data
     * @param $timeout
     * @return bool
     */
    protected function setValue($key, $data, $timeout)
    {
        $this->gc();
        $cacheFile = $this->getFileName($key);
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, $this->mode, true);
        }
        if (@file_put_contents($cacheFile, $data, LOCK_EX) !== false) {
            if ($timeout <= 0) {
                $timeout = $this->timeout;
            }
            return $this->setExpirationTime($cacheFile, $timeout + time());
        }
        return false;
    }

    /**
     * Get base cache path
     *
     * @return string
     */
    protected function getBasePath()
    {
        if (is_null($this->_basePath)) {
            if ($this->_pathHandler) {
                $this->_basePath = $this->_pathHandler->get($this->path);
            } else {
                $this->_basePath = "/tmp";
            }
        }
        return $this->_basePath;
    }

    /**
     * Make file name by key
     *
     * @param $key
     * @return string
     */
    protected function getFileName($key)
    {
        $filePath = $key;
        if ($this->directoryLevel > 0 ) {
            $base = '';
            for ($i = 0; $i < $this->directoryLevel; ++$i) {
                if (($prefix = substr($key, $i + $i, 2)) !== false) {
                    $base .= DIRECTORY_SEPARATOR . $prefix;
                }
            }
            $filePath = $base . $filePath;
        }
        return $this->getBasePath() . DIRECTORY_SEPARATOR . $filePath . $this->extension;
    }

    /**
     * Checks clear
     *
     * @param bool $force
     * @param bool $expiredOnly
     */
    public function gc($force = false, $expiredOnly = true)
    {
        if ($force || mt_rand(0, 1000000) < $this->gcProbability) {
            $this->gcRecursive($this->getBasePath(), $expiredOnly);
        }
    }

    /**
     * Clean recursive
     *
     * @param $path
     * @param bool $expiredOnly
     */
    protected function gcRecursive($path, $expiredOnly = true)
    {
        if (($handle = opendir($path)) !== false) {
            while (($file = readdir($handle)) !== false) {
                if ($file[0] === '.') {
                    continue;
                }
                $fullPath = $path . DIRECTORY_SEPARATOR . $file;
                if (is_dir($fullPath)) {
                    $this->gcRecursive($fullPath, $expiredOnly);
                    if (!$expiredOnly) {
                        @rmdir($fullPath);
                    }
                } elseif (!$expiredOnly || $expiredOnly && $this->isExpire($fullPath)) {
                    @unlink($fullPath);
                }
            }
            closedir($handle);
        }
    }

    /**
     * Set expiration time to file
     *
     * @param $fullPath
     * @param $timeout
     * @return bool
     */
    protected function setExpirationTime($fullPath, $timeout)
    {
        return @touch($fullPath, $timeout);
    }

    /**
     * Check expiration of cache file
     *
     * @param $fullPath
     * @return bool
     */
    protected function isExpire($fullPath)
    {
        return @filemtime($fullPath) < time();
    }
}