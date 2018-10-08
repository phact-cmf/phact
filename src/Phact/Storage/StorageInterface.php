<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 23/09/2018 14:21
 */

namespace Phact\Storage;

use Phact\Storage\Files\StorageFile;

interface StorageInterface
{
    /**
     * @param $path
     * @return mixed
     */
    public function getPath($path);

    /**
     * @param $path
     * @return mixed
     */
    public function getExtension($path);


    /**
     * @param $path
     * @return int|null
     */
    public function getSize($path);

    /**
     * @param $path
     * @return mixed
     */
    public function delete($path);

    /**
     * @return mixed save method
     */
    public function save($filename, $content);

    /**
     * @param $path
     * @return mixed
     */
    public function getUrl($path);

    /**
     * Retrieves the list of files and directories from storage py path
     * @param $path
     */
    public function dir($path);

    /**
     * Make directory
     * @param $path
     */
    public function mkDir($path);

    /**
     * @param $name string
     * @return mixed
     */
    public function setName($name);

    /**
     * @param $name
     * @return mixed
     */
    public function getName();

    /**
     * @param $path string path file
     * @return string content
     */
    public function getContent($path);

    /**
     * Copy storage file (set content from given $file to $filename path)
     * @param $filename
     * @param StorageFile $file
     * @return mixed
     */
    public function copyStorageFile($filename, StorageFile $file);

    /**
     * @param $from
     * @param $to
     * @return string file path after save
     */
    public function copy($fromPath, $toPath);

    /**
     * Check that file exists
     * @param $path
     * @return bool
     */
    public function isFile($path);

    /**
     * Check that file exists
     * @param $path
     * @return bool
     */
    public function isDir($path);
}