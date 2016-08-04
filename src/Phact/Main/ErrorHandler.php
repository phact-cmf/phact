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
 * @date 04/08/16 11:47
 */

namespace Phact\Main;

use ErrorException;
use Exception;
use Phact\Exceptions\HttpException;
use Phact\Helpers\Http;
use Phact\Helpers\SmartProperties;
use Phact\Template\Renderer;

class ErrorHandler
{
    use SmartProperties, Renderer;

    public $errorTemplate = 'error.tpl';
    public $exceptionTemplate = 'exception.tpl';

    public $debug = false;

    public function init()
    {
        $this->setHandlers();
    }

    public function setHandlers()
    {
        ini_set('display_errors', false);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    public function unsetHandlers()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    public function handleError($code, $message, $file, $line)
    {
        throw new ErrorException($message, $code, $code, $file, $line);
    }

    public function handleException($exception)
    {
        $this->unsetHandlers();

        $code = 500;
        if ($exception instanceof HttpException) {
            $code = $exception->status;
        }

        if (!headers_sent()) {
            header("HTTP/1.0 {$code} " . Http::getMessage($code));
        }

        try {
            $this->renderException($exception, $code);
        } catch (Exception $e) {
            if ($this->debug) {
                debug_print_backtrace();
            } else {
                echo 'Internal server error';
            }
        }
    }

    public function renderException($exception, $code)
    {
        $template = $this->errorTemplate;
        if ($this->debug) {
            $template = $this->exceptionTemplate;
        }
        self::renderTemplate($template, [
            'exception' => $exception,
            'status' => $code
        ]);
    }
}