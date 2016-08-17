<?php

namespace Phact\Storage\Files;

interface FileInterface
{
    /**
     * @return mixed
     */
    public function getPath();

    /**
     * @return string|null file ext
     */
    public function getExt();

    /**
     * @return string base file name
     */
    public function getName();

    /**
     * @return string file content
     */
    public function getContent();

}