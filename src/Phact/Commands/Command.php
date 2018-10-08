<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 15/09/16 11:37
 */

namespace Phact\Commands;

use Phact\Helpers\ClassNames;

/**
 * Console command
 *
 * Class Command
 * @package Phact\Commands
 */
abstract class Command implements CommandInterface
{
    use ClassNames;

    protected $_foregroundColors = [];
    protected $_backgroundColors = [];

    protected function setUpColors()
    {
        $this->_foregroundColors['black'] = '0;30';
        $this->_foregroundColors['dark_gray'] = '1;30';
        $this->_foregroundColors['blue'] = '0;34';
        $this->_foregroundColors['light_blue'] = '1;34';
        $this->_foregroundColors['green'] = '0;32';
        $this->_foregroundColors['light_green'] = '1;32';
        $this->_foregroundColors['cyan'] = '0;36';
        $this->_foregroundColors['light_cyan'] = '1;36';
        $this->_foregroundColors['red'] = '0;31';
        $this->_foregroundColors['light_red'] = '1;31';
        $this->_foregroundColors['purple'] = '0;35';
        $this->_foregroundColors['light_purple'] = '1;35';
        $this->_foregroundColors['brown'] = '0;33';
        $this->_foregroundColors['yellow'] = '1;33';
        $this->_foregroundColors['light_gray'] = '0;37';
        $this->_foregroundColors['white'] = '1;37';

        $this->_backgroundColors['black'] = '40';
        $this->_backgroundColors['red'] = '41';
        $this->_backgroundColors['green'] = '42';
        $this->_backgroundColors['yellow'] = '43';
        $this->_backgroundColors['blue'] = '44';
        $this->_backgroundColors['magenta'] = '45';
        $this->_backgroundColors['cyan'] = '46';
        $this->_backgroundColors['light_gray'] = '47';
    }

    public function color($string, $foreground_color = null, $background_color = null) {
        if (!$this->_foregroundColors || !$this->_backgroundColors) {
            $this->setUpColors();
        }
        $colored_string = "";
        if (isset($this->_foregroundColors[$foreground_color])) {
            $colored_string .= "\033[" . $this->_foregroundColors[$foreground_color] . "m";
        }
        if (isset($this->_backgroundColors[$background_color])) {
            $colored_string .= "\033[" . $this->_backgroundColors[$background_color] . "m";
        }
        $colored_string .=  $string . "\033[0m";
        return $colored_string;
    }

    /**
     * Description for help
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * Get command name
     * @return string
     */
    public function getName()
    {
        return preg_replace('/Command$/', '', static::classNameShort());
    }

    /**
     * Verbose command info
     * @return string
     */
    public function getVerbose()
    {
        $description = $this->getDescription();
        return $this->getModuleName() . ' ' . $this->getName() . ($description ? " - {$description}": '');
    }

    public function __toString()
    {
        return $this->getVerbose();
    }
}