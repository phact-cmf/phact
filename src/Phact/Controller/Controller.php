<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 14/06/16 08:01
 */

namespace Phact\Controller;

use Phact\Di\Container;
use Phact\Di\ContainerInterface;
use Phact\Event\Events;
use Phact\Exceptions\HttpException;
use Phact\Exceptions\InvalidConfigException;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;
use Phact\Orm\Model;
use Phact\Request\HttpRequestInterface;
use Phact\Template\RendererInterface;
use ReflectionMethod;

/**
 * Class Controller
 *
 * @property \Phact\Request\HttpRequestInterface $request
 *
 * @package Phact\Controller
 */
class Controller implements ControllerInterface
{
    use SmartProperties, Events;

    /**
     * @var HttpRequestInterface
     */
    protected $_request;

    /**
     * @var RendererInterface
     */
    protected $_renderer;

    /**
     * @var string|null Default action
     */
    public $defaultAction;

    public function __construct(HttpRequestInterface $request, RendererInterface $renderer)
    {
        $this->_request = $request;
        $this->_renderer = $renderer;
    }

    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * @param string $template Path to template
     * @param array $params
     * @return string
     */
    public function render($template, $params = [])
    {
        return $this->_renderer->render($template, $params);
    }

    public function redirect($url, $data = [], $status = 302)
    {
        $this->_request->redirect($url, $data, $status);
    }

    public function refresh()
    {
        $this->_request->refresh();
    }

    public function beforeActionInternal($action, $params)
    {
        $this->beforeAction($action, $params);
        $this->eventTrigger("controller.beforeAction", [$params], $this);
    }

    public function afterActionInternal($action, $params, $response)
    {
        $this->afterAction($action, $params, $response);
        $this->eventTrigger("controller.afterAction", [$params, $response], $this);
    }

    public function beforeAction($action, $params)
    {
    }

    public function afterAction($action, $params, $response)
    {
    }

    public function error($code = 404, $message = null)
    {
        throw new HttpException($code, $message);
    }

    public function jsonResponse($data = [])
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * @param $class string|Model
     * @param $filter array|string|int
     * @return Model
     * @throws HttpException
     */
    public function getOr404(Model $class, $filter)
    {
        if (!is_array($filter)) {
            $filter = ['id' => $filter];
        }
        $model = $class::objects()->filter($filter)->get();
        if (!$model) {
            $this->error(404);
        }
        return $model;
    }
}