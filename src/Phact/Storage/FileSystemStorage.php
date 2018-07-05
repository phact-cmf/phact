<?php

namespace Phact\Storage;


use DirectoryIterator;
use FilesystemIterator;
use Phact\Exceptions\InvalidConfigException;
use Phact\Helpers\FileHelper;
use Phact\Helpers\Paths;
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
    public $basePath = '';
    /**
     * @var string
     */
    public $baseUrl = '/media/';


    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!is_dir($this->basePath)) {
            if (!$this->createBaseDirectory()) {
                throw new InvalidConfigException("Directory for file storage system not found. Base path: {$this->basePath}");
            }
            $this->basePath = $this->getBaseDir();
        }

        $this->basePath = realpath(rtrim($this->basePath, DIRECTORY_SEPARATOR));
    }

    /**
     * @return bool, true if directory success
     * created
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
     */
    public function getBaseDir()
    {

        $path = Paths::get($this->folderName);

        if ($path === null || $path === false) {
            throw new InvalidConfigException("Folder name {$path} must be valid path");
        }
        if (is_dir($path) && !is_writable($path)) {
            throw new InvalidConfigException("Directory $path must be writable");
        }
        return $path;
    }

    /**
     * @param $name
     * @param string $mode
     * @return null|string
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
     * @param $name
     * @param $content
     * @return bool
     */
    public function writeFile($name, $content)
    {
        $this->prepareFilePath($name);
        return file_put_contents($this->getAbsolutePath($name), $content) !== false;
    }

    /**
     * @param $name
     * @return int|null
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
     * @param $name
     * @return bool
     */
    public function exists($name)
    {
        return is_file($this->getPath($name));
    }

    /**
     * @param $name
     * @return string
     */
    public function getPath($name)
    {
        return realpath($this->getAbsolutePath($name));
    }


    /**
     * @param $path string
     * @return bool
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

    public function prepareFilePath($filename)
    {
        $directory = $this->getAbsolutePath(dirname($filename));

        if ($directory !== '.' && !is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        return $directory;
    }

    public function getAbsolutePath($path)
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $path;
    }

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
                $path = str_replace($this->basePath . DIRECTORY_SEPARATOR, '', $iteratedPath->getPathname());
                $folderStructure[$key][] = [
                    'path' => $path,
                    'url' => $this->getUrl($path),
                    'name' => basename($path)
                ];
            }
        }

        return $folderStructure;
    }

    public function mkDir($path)
    {
        $path = $this->basePath . DIRECTORY_SEPARATOR . $path;
        return file_exists($path) ? false : mkdir($path);
    }
}