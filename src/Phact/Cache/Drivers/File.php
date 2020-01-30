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

use Phact\Cache\AbstractCacheDriver;
use Phact\Components\PathInterface;

class File extends AbstractCacheDriver
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
    public $mode = 0775;

    /**
     * Base path
     *
     * @var string
     */
    protected $basePath;

    /**
     * @var PathInterface
     */
    protected $pathHandler;

    public function __construct(PathInterface $pathHandler)
    {
        $this->pathHandler = $pathHandler;
    }

    public function clear(): bool
    {
        $this->gcRecursive($this->getBasePath(), false);
        return true;
    }

    protected function hasValue(string $key): bool
    {
        $filePath = $this->getFileName($key);
        return is_file($filePath) && @filemtime($filePath) > time();
    }

    /**
     * Read cache value from file
     *
     * @param $key
     * @return bool|null|string
     */
    protected function getValue(string $key)
    {
        if ($this->hasValue($key)) {
            $filePath = $this->getFileName($key);
            $fp = @fopen($filePath, 'rb');
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
     * @inheritDoc
     */
    protected function setValue(string $key, $data, int $ttl): bool
    {
        $this->gc();
        $cacheFile = $this->getFileName($key);
        $dir = dirname($cacheFile);
        if (!is_dir($dir) && !mkdir($dir, $this->mode, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        if (@file_put_contents($cacheFile, $data, LOCK_EX) !== false) {
            return $this->setExpirationTime($cacheFile, $ttl);
        }
        return false;
    }


    /**
     * @inheritDoc
     */
    protected function deleteValue(string $key): bool
    {
        if ($this->hasValue($key)) {
            return @unlink($this->getFileName($key));
        }

        return false;
    }

    /**
     * Get base cache path
     *
     * @return string
     */
    protected function getBasePath(): string
    {
        if (!$this->basePath) {
            $this->basePath = sys_get_temp_dir() || '/tmp';

            if ($this->pathHandler) {
                $this->basePath = $this->pathHandler->get($this->path);
            }
        }

        return $this->basePath;
    }

    /**
     * Make file name by key
     *
     * @param $key
     * @return string
     */
    protected function getFileName($key): string
    {
        $base = '';
        $filePath = $key;
        if ($this->directoryLevel > 0) {
            for ($i = 0; $i < $this->directoryLevel; ++$i) {
                if (($prefix = substr($key, $i + $i, 2)) !== false) {
                    $base .= $prefix . DIRECTORY_SEPARATOR;
                }
            }
        }

        return $this->getBasePath() . DIRECTORY_SEPARATOR . $base . $this->prefix . $filePath . $this->extension;
    }

    /**
     * Checks clear
     *
     * @param bool $force
     * @param bool $expiredOnly
     */
    public function gc($force = false, $expiredOnly = true): void
    {
        /** @noinspection RandomApiMigrationInspection */
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
    protected function gcRecursive($path, $expiredOnly = true): void
    {
        if (($handle = @opendir($path)) !== false) {
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
                } elseif (!$expiredOnly || ($expiredOnly && $this->isExpire($fullPath))) {
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
    protected function setExpirationTime($fullPath, $timeout): bool
    {
        return @touch($fullPath, $timeout);
    }

    /**
     * Check expiration of cache file
     *
     * @param $fullPath
     * @return bool
     */
    protected function isExpire($fullPath): bool
    {
        return @filemtime($fullPath) < time();
    }
}