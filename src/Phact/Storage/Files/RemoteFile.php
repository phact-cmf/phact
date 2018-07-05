<?php

namespace Phact\Storage\Files;

use Exception;

class RemoteFile extends File
{
    public function __construct($path)
    {
        if (!$this->urlExists($path)) {
            throw new Exception("File {$path} not found");
        }

        list($size, $type) = $this->getInfo($path);
        $this->path = $path;
        $this->name = basename($path);
        $this->size = $size;
        $this->type = $type;
    }

    public function urlExists($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code == 200;
    }

    public function getInfo($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $mime = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        return [$size, $mime];
    }
}
