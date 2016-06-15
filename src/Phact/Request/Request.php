<?php

namespace Phact\Request;

use Phact\Helpers\Collection;
use Phact\Helpers\SmartProperties;

/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company HashStudio
 * @site http://hashstudio.ru
 * @date 13/06/16 11:08
 */
class Request
{
    use SmartProperties;

    /**
     * @var Http
     */
    public $http;

    /**
     * @var Collection
     */
    public $get;

    /**
     * @var Collection
     */
    public $post;

    public $files;

    public $flash;

    public $session;

    public function init()
    {
        $this->get = new Collection($_GET);
        $this->post = new Collection($_POST);
        $this->http = new Http();
    }
}