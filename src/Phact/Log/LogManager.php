<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company OrderTarget
 * @site http://ordertarget.ru
 * @date 26/04/18 18:23
 */

namespace Phact\Log;


use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Logger as MonoLogger;
use Phact\Helpers\Configurator;
use Phact\Helpers\SmartProperties;

class LogManager
{
    use SmartProperties, LoggerHandle;

    /**
     * @var FormatterInterface[]
     */
    protected $_formatters = [];

    /**
     * @var array
     */
    protected $_formattersConfig = [];

    /**
     * @var HandlerInterface[]
     */
    protected $_handlers = [];

    /**
     * @var array
     */
    protected $_handlersConfig = [];

    /**
     * @var MonoLogger[]
     */
    protected $_loggers = [];

    /**
     * @var array
     */
    protected $_loggersConfig = [];

    public function setFormatters($config)
    {
        $this->_formattersConfig = $config;
    }

    public function setHandlers($config)
    {
        $this->_handlersConfig = $config;
    }

    public function setLoggers($config)
    {
        $this->_loggersConfig = $config;
    }

    /**
     * @param $name
     * @return MonoLogger
     * @throws \Exception
     */
    public function getLogger($name = 'default')
    {
        if (!isset($this->_loggersConfig[$name])) {
            $name = 'default';
        }
        if (!isset($this->_loggers[$name])) {
            if (!isset($this->_loggersConfig[$name])) {
                if ($name == 'default') {
                    $this->_loggers[$name] = new MonoLogger($name, [
                        new NullHandler()
                    ]);
                    return $this->_loggers[$name];
                }
                throw new \Exception("Undefined logger '{$name}'");
            }
            $config = $this->_loggersConfig[$name];
            $handlers = isset($config['handlers']) ? $config['handlers'] : [];
            unset($config['handlers']);
            $afterInit = isset($config['afterInit']) ? $config['afterInit'] : null;
            unset($config['afterInit']);
            if (!isset($config['__construct'])) {
                $config['__construct'] = [$name];
            }
            /** @var MonoLogger $logger */
            $logger = Configurator::create($config);
            foreach ($handlers as $handlerName) {
                $logger->pushHandler($this->getHandler($handlerName));
            }
            if (is_callable($afterInit)) {
                $afterInit($logger);
            }
            $this->_loggers[$name] = $logger;
        }
        return $this->_loggers[$name];
    }

    /**
     * @param $name
     * @return HandlerInterface
     * @throws \Exception
     */
    public function getHandler($name)
    {
        if (!isset($this->_handlers[$name])) {
            if (!isset($this->_handlersConfig[$name])) {
                throw new \Exception("Undefined handler '{$name}'");
            }
            $config = $this->_handlersConfig[$name];
            $formatter = isset($config['formatter']) ? $config['formatter'] : null;
            unset($config['formatter']);
            $processors = isset($config['processors']) ? $config['processors'] : [];
            unset($config['processors']);
            $afterInit = isset($config['afterInit']) ? $config['afterInit'] : null;
            unset($config['afterInit']);

            /** @var HandlerInterface $handler */
            $handler = Configurator::create($config);
            if ($formatter) {
                $handler->setFormatter($this->getFormatter($formatter));
            }
            foreach ($processors as $processor) {
                $handler->pushProcessor($processor);
            }
            if (is_callable($afterInit)) {
                $afterInit($handler);
            }
            $this->_handlers[$name] = $handler;
        }
        return $this->_handlers[$name];
    }

    /**
     * @param $name
     * @return FormatterInterface
     * @throws \Exception
     */
    public function getFormatter($name)
    {
        if (!isset($this->_formatters[$name])) {
            if (!isset($this->_formattersConfig[$name])) {
                throw new \Exception("Undefined formatter '{$name}'");
            }
            $config = $this->_formattersConfig[$name];

            $afterInit = isset($config['afterInit']) ? $config['afterInit'] : null;
            unset($config['afterInit']);

            /** @var FormatterInterface $formatter */
            $formatter = Configurator::create($config);
            if (is_callable($afterInit)) {
                $afterInit($formatter);
            }

            $this->_formatters[$name] = $formatter;
        }
        return $this->_formatters[$name];
    }
}