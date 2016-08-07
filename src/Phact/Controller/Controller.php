<?php
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
 * @date 14/06/16 08:01
 */

namespace Phact\Controller;

use Phact\Exceptions\InvalidConfigException;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;
use Phact\Request\Request;
use ReflectionMethod;

/**
 * Class Controller
 *
 * @property \Phact\Request\HttpRequest $request
 *
 * @package Phact\Controller
 */
class Controller
{
    use SmartProperties;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * @var string|null Default action
     */
    public $defaultAction;

    public function __construct($request)
    {
        $this->_request = $request;
    }

    public function getRequest()
    {
        return $this->_request;
    }

    public function run($action = null, $params = [])
    {
        if (!$action) {
            $action = $this->defaultAction;
        }
        if (method_exists($this, $action)) {
            $this->runAction($action, $params);
        } else {
            $class = self::class;
            throw new InvalidConfigException("There is no action {$action} in controller {$class}");
        }
    }

    public function runAction($action, $params = [])
    {
        $method = new ReflectionMethod($this, $action);
        $ps = [];
        if ($method->getNumberOfParameters() > 0) {
            foreach ($method->getParameters() as $param) {
                $name = $param->getName();
                if (isset($params[$name])) {
                    if ($param->isArray()) {
                        $ps[] = is_array($params[$name]) ? $params[$name] : [$params[$name]];
                    } elseif (!is_array($params[$name])) {
                        $ps[] = $params[$name];
                    } else {
                        return false;
                    }
                } elseif ($param->isDefaultValueAvailable()) {
                    $ps[] = $param->getDefaultValue();
                } else {
                    $class = self::class;
                    throw new InvalidConfigException("Param {$name} for action {$action} in controller {$class} must be defined. Please, check your routes.");
                }
            }
            $method->invokeArgs($this, $ps);
        } else {
            $this->{$action}();
        }
        return true;
    }

    /**
     * @param string $template Path to template
     * @param array $params
     * @return string
     */
    public function render($template, $params = [])
    {
        return Phact::app()->template->render($template, $params);
    }

    public function redirect($url, $data = [], $status = 302)
    {
        $this->request->redirect($url, $data, $status);
    }

    public function refresh()
    {
        $this->request->refresh();
    }
}