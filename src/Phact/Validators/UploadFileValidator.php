<?php
/**
 * Created by PhpStorm.
 * User: aleksandrgordeev
 * Date: 10.08.16
 * Time: 21:36
 */

namespace Phact\Validators;


use Phact\Helpers\FileHelper;
use Phact\Storage\Files\UploadedFile;

class UploadFileValidator extends FormFieldValidator
{
    /**
     * @var null
     */
    public $allowedTypes = [];
    /**
     * @var null|int maximum file size or null for unlimited.
     */
    public $maxSize;


    public function __construct($allowedTypes = null, $maxSize = null)
    {
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;
    }

    protected function codeToMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;
            default:
                $message = "Unknown upload error";
                break;
        }
        return $message;
    }

    public static function checkUploadSuccessCode($upload)
    {
        return (isset($upload['error']) && $upload['error'] == UPLOAD_ERR_OK);
    }

    public function validate($value)
    {
        $isValid = true;
        $messages = [];

        if ($value instanceof UploadedFile) {

            if (!$value->error == UPLOAD_ERR_OK){
                $isValid  = false;
                $messages[] = $this->codeToMessage($value['error']);
            }

            if ($this->maxSize && $isValid && !$this->validateMaxSize($value->size)){
                $isValid = false;
                $maxSize = FileHelper::bytesToSize($this->maxSize);
                $messages[] = "File {$value->name} is to large. Max file size {$maxSize}";
            }

            if ($this->allowedTypes && $isValid && !$this->validateFileType($value)){
                $availFileTypes = implode(',', $this->allowedTypes);
                $messages[] = "Incorrect file type {$value->name}. Available {$availFileTypes}";
            }
        }


        return (empty($messages)) ? true : $messages;
    }

    public function validateMaxSize($size)
    {
        if ($size > $this->maxSize){
            return false;
        }
        return true;
    }

    /**
     * @param $value UploadedFile
     * @return bool
     */
    public function validateFileType($value)
    {
        $ext = pathinfo($value->name, PATHINFO_EXTENSION);

        if (!in_array($ext, $this->allowedTypes)){
            return false;
        }
        return true;

    }
}