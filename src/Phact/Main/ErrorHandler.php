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

class ErrorHandler
{
    use SmartProperties, Renderer, Logger;

    public $errorTemplate = 'error.tpl';
    public $exceptionTemplate = 'exception.tpl';

    public $debug = false;

    /**
     * @var RendererInterface
     */
    protected $_renderer;

    /**
     * @var PathInterface
     */
    protected $_path;

    public function __construct(
        PathInterface $path = null,
        RendererInterface $renderer = null,
        LoggerInterface $logger = null)
    {
        $this->_path = $path;
        $this->_renderer = $renderer;
        $this->_logger = $logger;
        $this->setHandlers();
    }

    public function setHandlers()
    {
        ini_set('display_errors', 0);
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
        register_shutdown_function([$this, 'handleFatal']);
    }

    public function unsetHandlers()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    public function handleFatal()
    {
        $error = error_get_last();
        if( $error !== NULL) {
            $this->handleError($error["type"], $error["message"], $error["file"], $error["line"]);
        }
    }

    public function handleError($code, $message, $file, $line)
    {
        $this->handleException(new ErrorException($message, $code, $code, $file, $line), debug_backtrace());
    }

    public function handleException($exception, $traceRaw = null)
    {
        $this->unsetHandlers();

        $code = 500;
        if ($exception instanceof HttpException) {
            $code = $exception->status;
        }

        if (!headers_sent()) {
            header("HTTP/1.0 {$code} " . Http::getMessage($code));
        }

//        try {
//            if (ob_get_length()) ob_clean();
//            $this->renderException($exception, $code, $traceRaw);
//        } catch (Exception $e) {
//            if ($this->debug) {
//                echo PHP_EOL;
//                echo debug_print_backtrace();
//            } else {
//                echo 'Internal server error';
//            }
//        }
    }

    /**
     * @param $exception Exception
     * @param $code
     * @param null $traceRaw
     */
    public function renderException($exception, $code, $traceRaw = null)
    {
        $template = $this->errorTemplate;
        if ($this->debug) {
            $template = $this->exceptionTemplate;
        }

        $trace = [];
        $traceRaw = $traceRaw ?: $exception->getTrace();
        $closestLines = 5;
        if ($this->_path && ($base = $this->_path->get('base'))) {
            $basePath = realpath($base);
        } else {
            $basePath = '';
        }
        foreach ($traceRaw as $traceItem) {
            if (isset($traceItem['line'])) {
                $line = $traceItem['line'];
                $startLine = $line - $closestLines;
                $endLine = $line + $closestLines;
                if ($startLine < 0) {
                    $endLine += abs($startLine);
                    $startLine = 0;
                }
                $itemLines = [];
                $fileName = $traceItem['file'];
                try {
                    if (file_exists($fileName) && ($lines = @file($fileName))) {
                        $itemLines = array_slice($lines, $startLine, $endLine - $startLine, true);
                    } else {
                        continue;
                    }
                } catch (Exception $e) {

                }

                if (substr($fileName, 0, strlen($basePath)) == $basePath) {
                    $fileName = substr($fileName, strlen($basePath));
                }

                $trace[] = [
                    'fileName' => $fileName,
                    'trace' => $traceItem,
                    'startLine' => $startLine,
                    'endLine' => $endLine,
                    'itemLines' => $itemLines
                ];
            }
        }

        if ($code == 404) {
            $this->logDebug("Page not found");
        } else {
            $this->logError((string) $exception);
        }

        if (php_sapi_name() == 'cli') {
            echo "Exception: " . $exception->getMessage() . PHP_EOL;
            echo "Trace: " . PHP_EOL;
            print_r($trace);
        } else {
            echo $this->_renderer->render($template, [
                'exception' => $exception,
                'code' => $code,
                'trace' => $trace
            ]);
        }
        die();
    }
}