<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 04/08/16 11:47
 */

namespace Phact\Main;

use ErrorException;
use Exception;
use Phact\Components\PathInterface;
use Phact\Exceptions\HttpException;
use Phact\Helpers\Http;
use Phact\Helpers\SmartProperties;
use Phact\Log\Logger;
use Phact\Template\Renderer;
use Phact\Template\RendererInterface;
use Psr\Log\LoggerInterface;
use Whoops\Exception\Inspector;
use Whoops\RunInterface;
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\PlainTextHandler;

class ErrorHandler
{
    use SmartProperties, Renderer, Logger;

    public $errorTemplate = 'error.tpl';

    protected $_debug = false;

    /**
     * @var RendererInterface
     */
    protected $_renderer;

    /**
     * @var PathInterface
     */
    protected $_path;

    /**
     * @var RunInterface
     */
    protected $_run;

    public function __construct(
        $debug = false,
        PathInterface $path = null,
        RendererInterface $renderer = null,
        LoggerInterface $logger = null,
        RunInterface $run = null)
    {
        $this->_debug = $debug;
        $this->_path = $path;
        $this->_renderer = $renderer;
        $this->_logger = $logger;

        if (!$run) {
            $this->_run = new Run;
            if (!$this->_debug) {
                $handler = function ($exception, $inspector, $run) {
                    $this->handleException($exception);
                };
            } else {
                if (php_sapi_name() == 'cli') {
                    $handler = new PlainTextHandler();
                } else {
                    $handler = new ExceptionPageHandler();
                    if ($path = $this->_path->get('root')) {
                        $handler->setApplicationRootPath($path);
                    }
                }
            }
            $this->_run->pushHandler($handler);
            $this->_run->pushHandler(function ($exception, $inspector, $run) {
                $this->logException($exception);
                $this->setHeaders($exception);
            });
        }
        $this->_run->register();
    }

    /**
     * Log exception data
     * @param $exception
     */
    public function logException($exception)
    {
        $code = $this->getExceptionCode($exception);
        if ($code === 404) {
            $this->logDebug('Page not found', [
                'exception' => $exception
            ]);
        } else {
            $this->logError((string) $exception, [
                'exception' => $exception
            ]);
        }
    }

    /**
     * Set headers code based on exception
     * @param Exception $exception
     */
    public function setHeaders($exception)
    {
        $code = $this->getExceptionCode($exception);
        if (!headers_sent()) {
            header("HTTP/1.0 {$code} " . Http::getMessage($code));
        }
    }

    /**
     * @param Exception $exception
     * @return int
     */
    public function getExceptionCode($exception)
    {
        $code = 500;
        if ($exception instanceof HttpException) {
            $code = $exception->status;
        }
        return $code;
    }

    /**
     * @param $exception
     */
    public function handleException($exception)
    {
        $code = $this->getExceptionCode($exception);
        try {
            if (ob_get_length()) ob_clean();
            $this->renderException($exception, $code);
        } catch (Exception $e) {
            echo 'Internal server error: ' . $e->getMessage();
        }
    }

    /**
     * @param $exception Exception
     * @param $code
     * @param null $traceRaw
     */
    public function renderException($exception, $code)
    {
        if (php_sapi_name() == 'cli') {
            echo "Exception: " . $exception->getMessage() . PHP_EOL;
        } else {
            echo $this->_renderer->render($this->errorTemplate, [
                'exception' => $exception,
                'code' => $code
            ]);
        }
        die();
    }
}