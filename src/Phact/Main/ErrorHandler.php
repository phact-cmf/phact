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
                    $handler = new PrettyPageHandler();
                }
            }
            $this->_run->pushHandler($handler);
        }
        $this->_run->register();
    }

    /**
     * @param $exception
     */
    public function handleException($exception)
    {
        $code = 500;
        if ($exception instanceof HttpException) {
            $code = $exception->status;
        }

        if (!headers_sent()) {
            header("HTTP/1.0 {$code} " . Http::getMessage($code));
        }

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
        if ($code == 404) {
            $this->logDebug("Page not found");
        } else {
            $this->logError((string) $exception);
        }

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