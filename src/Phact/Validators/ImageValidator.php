<?php
/**
 * Created by PhpStorm.
 * User: aleksandrgordeev
 * Date: 11.08.16
 * Time: 21:02
 */

namespace Phact\Validators;

use Phact\Storage\Files\UploadedFile;

class ImageValidator extends UploadFileValidator
{

    public function validate($value)
    {
        $parentValidationResult = parent::validate($value);
        $messages = [];

        if($parentValidationResult == true && $value instanceof UploadedFile){

            $size = @getimagesize($value->path);
            if($size == false){
                $messages[] = 'Файл не является изображением';
            }
        }
        return empty($messages) ? true : $messages;
    }
}