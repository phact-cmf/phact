<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 14/11/2018 12:04
 */

namespace Phact\Main;

use Whoops\Exception\Frame;
use Whoops\Exception\FrameCollection;
use Whoops\Exception\Inspector;
use Whoops\Handler\PrettyPageHandler;

class ExceptionPageHandler extends PrettyPageHandler
{
    protected function getExceptionFrames()
    {
        $frames = parent::getExceptionFrames();
        $newFrames = [];
        /** @var Frame $frame */
        foreach ($frames as $frame) {
            $frameRaw = $frame->getRawFrame();
            $filePath = $frame->getFile();
            if ($filePath != "Unknown" && $filePath != '[internal]' && !is_file($filePath)) {
                $frameRaw['file'] = null;
            }
            $newFrames[] = $frameRaw;
        }
        return new FrameCollection($newFrames);
    }
}