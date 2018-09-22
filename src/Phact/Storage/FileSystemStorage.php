<?php

namespace Phact\Storage;

use DirectoryIterator;
use FilesystemIterator;
use Phact\Components\PathInterface;
use Phact\Exceptions\DependencyException;
use Phact\Exceptions\InvalidConfigException;
use Phact\Helpers\FileHelper;
use Phact\Helpers\SmartProperties;
use Phact\Helpers\Text;
use Phact\Storage\Files\File;
use Phact\Storage\Files\LocalFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileSystemStorage extends Storage
{
    use SmartProperties;

    /**
     * @var string
     */
    public $folderName = 'www.media';
    /**
     * @var string
     */
    public $baseUrl = '/media/';

    /**
     * @var string
     */
    protected $_basePath;

    /**
     * @var PathInterface
     */
    protected $_path;

    public function __construct(PathInterface $path = null)
    {
        $this->_path = $path;
    }

    /**
     * @param string $path
     * @throws InvalidConfigException
     */
    public function setBasePath(string $path)
    {
        if (!is_dir($path) || !is_writable($path)) {
            throw new InvalidConfigException("Path $path must be writable and directory");
        }
        $this->_basePath = $path;
    }

    /**
     * @return bool|string
     * @throws InvalidConfigException
     * @throws DependencyException
     */
    public function getBasePath()
    {
        if (is_null($this->_basePath)) {
            if (!is_dir($this->_basePath)) {
                if (!$this->createBaseDirectory()) {
                    throw new InvalidConfigException("Directory for file storage system not found. Base path: {$this->basePath}");
                }
                $this->_basePath = $this->getBaseDir();
            }

            $this->_basePath = realpath(rtrim($this->_basePath, DIRECTORY_SEPARATOR));
        }
        return $this->_basePath;
    }

    /**
     * @throws InvalidConfigException
     * @return bool, true if directory successfully created
     * @throws DependencyException
     */
    public function createBaseDirectory()
    {
        $directory = $this->getBaseDir();
        if (!file_exists($directory) && !is_file($directory) && !is_dir($directory)) {
            return @mkdir($directory, 0777, true);
        }
        return is_dir($directory);
    }

    /**
     * @return null|string path to media directory
     * @throws InvalidConfigException
     * @throws DependencyException
     */
    public function getBaseDir()
    {
        if (!$this->_path) {
            throw new DependencyException("Required dependency " . PathInterface::class . " is not injected");
        }
        $path = $this->_path->get($this->folderName);

        if ($path === null || $path === false) {
            throw new InvalidConfigException("Folder name {$path} must be valid path");
        }
        if (is_dir($path) && !is_writable($path)) {
            throw new InvalidConfigException("Directory $path must be writable");
        }
        return $path;
    }

    /**
     * Read file
     *
     * @param $name
     * @param string $mode
     * @return null|string
     * @throws InvalidConfigException
     */
    public function readFile($name, $mode = 'r')
    {
        if (!$this->exists($name)) {
            return null;
        }

        $path = $this->getPath($name);

        if (!is_readable($path)) {
            return null;
        }

        $handle = fopen($path, $mode, false);
        $contents = fread($handle, filesize($path));
        fclose($handle);
        return $contents;
    }

    /**
     * Write content to file
     *
     * @param $name
     * @param $content
     * @return bool
     * @throws InvalidConfigException
     */
    public function writeFile($name, $content)
    {
        $this->prepareFilePath($name);
        return file_put_contents($this->getAbsolutePath($name), $content) !== false;
    }

    /**
     * Get file size
     *
     * @param $name
     * @return int|null
     * @throws InvalidConfigException
     */
    public function getSize($name)
    {
        $path = $this->getPath($name);
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }
        return filesize($this->getPath($name));

    }

    /**
     * Check that file is exist
     *
     * @param $name
     * @return bool
     * @throws InvalidConfigException
     */
    public function exists($name)
    {
        return is_file($this->getPath($name));
    }

    /**
     * @param $name
     * @return string
     * @throws InvalidConfigException
     */
    public function getPath($name)
    {
        return realpath($this->getAbsolutePath($name));
    }


    /**
     * @param $path string
     * @return bool
     * @throws InvalidConfigException
     */
    public function createDirectory($path)
    {
        $path = $this->getAbsolutePath($path);
        if (file_exists($path) && is_dir(($path))) {
            return true;
        } else {
            return mkdir($path);
        }
    }

    /**
     * @param $name string path
     * @return string
     */
    public function getUrl($name)
    {
        return $this->baseUrl . str_replace('\\', '/', $name);
    }

    /**
     * @param $name string path
     * @return bool|int
     */
    public function modifiedTime($name)
    {
        $path = $this->getPath($name);
        if (is_file($path) && is_readable($path)) {
            return fileatime($path);
        } else {
            return false;
        }
    }

    /**
     * @param $name string path
     * @return bool|int
     */
    public function getCreatedTime($path)
    {
        $path = $this->getPath($path);
        if (is_file($path) && is_readable($path)) {
            return filectime($path);
        } else {
            return false;
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getExtension($path)
    {
        return FileHelper::mbPathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function delete($path)
    {
        if (!$path) {
            return false;
        }
        $path = (is_file($path) || is_dir($path)) ? $path : $this->getPath($path);

        if (is_file($path)) {
            return unlink($path);
        } else if (is_dir($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $iterPath) {
                if ($iterPath->isDir()) {
                    rmdir($iterPath->getPathname());
                } else {
                    unlink($iterPath->getPathname());
                }
            }
            return rmdir($path);
        }
        return false;
    }

    /**
     * @param $content
     * @param $fileName
     * @return mixed save method
     * @throws InvalidConfigException
     */
    public function save($fileName, $content)
    {
        $fileName = $this->getAvailableName($fileName);

        if ($content instanceof File) {
            if ($content instanceof LocalFile) {
                $this->prepareFilePath($fileName);
                copy($content->getPath(), $this->getAbsolutePath($fileName));
                return $fileName;
            } else {
                $content = $content->getContent();
            }
        }

        if ($this->writeFile($fileName, $content)) {
            return $fileName;
        } else {
            return false;
        }
    }

    /**
     * Returns a filename that's free on the target storage system, and
     * available for new content to be written to.
     * @param $name
     * @return string
     */
    public function getAvailableName($name)
    {
        $dirname = dirname($name);
        $ext = FileHelper::mbPathinfo($name, PATHINFO_EXTENSION);
        $fileName = FileHelper::mbPathinfo($name, PATHINFO_FILENAME);

        $count = 0;
        $name = strtr("{dirname}/{filename}.{ext}", [
            '{dirname}' => $dirname,
            '{filename}' => $fileName,
            '{count}' => $count,
            '{ext}' => $ext
        ]);

        while ($this->exists($name)) {
            $count += 1;
            $name = strtr("{dirname}/{filename}_{count}.{ext}", [
                '{dirname}' => $dirname,
                '{filename}' => $fileName,
                '{count}' => $count,
                '{ext}' => $ext
            ]);
        }
        return $name;
    }


    /**
     * @param $path string path file
     * @return string content
     */
    public function getContent($path)
    {
        return $this->readFile($path);
    }

    /**
     * @param $from
     * @param $to
     * @return string file path after save
     */
    public function copy($from, $to)
    {
        $to = $this->getAvailableName($to);
        $this->prepareFilePath($to);
        return copy($this->getAbsolutePath($from), $this->getAbsolutePath($to));
    }

    /**
     * @param $filename
     * @return string
     * @throws InvalidConfigException
     */
    public function prepareFilePath($filename)
    {
        $directory = $this->getAbsolutePath(dirname($filename));

        if ($directory !== '.' && !is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        return $directory;
    }

    /**
     * @param $path
     * @return string
     * @throws InvalidConfigException
     */
    public function getAbsolutePath($path)
    {
        return $this->getBasePath() . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * Fetch items list from directory
     *
     * @param $path
     * @return array
     * @throws InvalidConfigException
     */
    public function dir($path)
    {
        $path = $this->getPath($path);
        $folderStructure = [
            'directories' => [],
            'files' => []
        ];

        foreach (new DirectoryIterator($path) as $iteratedPath) {
            if (!$iteratedPath->isDot() && !Text::startsWith(basename($iteratedPath->getPathname()), '.')) {
                $key = $iteratedPath->isDir() ? 'directories' : 'files';
                $path = str_replace($this->getBasePath() . DIRECTORY_SEPARATOR, '', $iteratedPath->getPathname());
                $folderStructure[$key][] = [
                    'path' => $path,
                    'url' => $this->getUrl($path),
                    'name' => basename($path)
                ];
            }
        }

        return $folderStructure;
    }

    /**
     * Make directory by path
     *
     * @param $path
     * @return bool
     * @throws InvalidConfigException
     */
    public function mkDir($path)
    {
        $path = $this->getBasePath() . DIRECTORY_SEPARATOR . $path;
        return file_exists($path) ? false : mkdir($path);
    }
}