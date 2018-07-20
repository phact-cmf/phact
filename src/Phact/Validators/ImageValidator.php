<?php
/**
 * Created by PhpStorm.
 * User: aleksandrgordeev
 * Date: 11.08.16
 * Time: 21:02
 */

namespace Phact\Validators;

use Phact\Storage\Files\UploadedFile;
use Phact\Translate\Translator;

class ImageValidator extends UploadFileValidator
{
    use Translator;

    public function validate($value)
    {
        $parentValidationResult = parent::validate($value);
        $messages = [];

        if ($parentValidationResult == true && $value instanceof UploadedFile) {
            $size = @getimagesize($value->path);
            if ($size == false) {
                $messages[] = self::t('The file is not an image', 'Phact.validators');;
            }
        }
        return empty($messages) ? true : $messages;
    }
}