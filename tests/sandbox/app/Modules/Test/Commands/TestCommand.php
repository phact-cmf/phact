<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 23/09/2018 13:16
 */

namespace Modules\Test\Commands;


use Phact\Commands\Command;
use Phact\Router\RouterInterface;

class TestCommand extends Command
{
    /**
     * @var RouterInterface
     */
    protected $_router;

    public function __construct(RouterInterface $router)
    {
        $this->_router = $router;
    }

    public function handle($arguments = [])
    {
        echo get_class($this->_router);
    }
}