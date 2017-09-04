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

    /**
     * @var array
     */
    public $errors = [];

    const UPLOAD_ERR_UNDEFINED = 100;
    const UPLOAD_ERR_MAX_SIZE = 101;
    const UPLOAD_ERR_INCORRECT_TYPE = 102;

    public function __construct($allowedTypes = null, $maxSize = null, $errors = null)
    {
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;
        if (!$errors) {
            $this->setDefaultErrors();
        } else {
            $this->errors = $errors;
        }
    }

    public function setDefaultErrors()
    {
        $this->errors = [
            UPLOAD_ERR_INI_SIZE => "Загруженный файл больше возможного размера",
            UPLOAD_ERR_FORM_SIZE => "Загруженный файл больше возможного размера",
            UPLOAD_ERR_PARTIAL => "Файл был загружен частично, повторите попытку",
            UPLOAD_ERR_NO_FILE => "Файл не был загружен",
            UPLOAD_ERR_NO_TMP_DIR => "Внутренняя ошибка загрузки (отсутствует временная папка)",
            UPLOAD_ERR_CANT_WRITE => "Внутренняя ошибка загрузки (диск недоступен для записи)",
            UPLOAD_ERR_EXTENSION => "Внутренняя ошибка загрузки",
            self::UPLOAD_ERR_UNDEFINED => "Неизвестная ошибка загрузки",
            self::UPLOAD_ERR_MAX_SIZE => "Загруженный файл больше возможного размера. Максимальный размер файла {size}",
            self::UPLOAD_ERR_INCORRECT_TYPE => "Некорректный тип файла. Доступные типы файлов {types}",
        ];
    }

    protected function codeToMessage($code)
    {
        if (isset($this->errors[$code])) {
            return $this->errors[$code];
        }
        if (isset($this->errors[self::UPLOAD_ERR_UNDEFINED])) {
            return $this->errors[self::UPLOAD_ERR_UNDEFINED];
        }
        return "Ошибка загрузки файла";
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
                if (isset($this->errors[self::UPLOAD_ERR_MAX_SIZE])) {
                    $error = $this->errors[self::UPLOAD_ERR_MAX_SIZE];
                } else {
                    $error = "File is too large. Max file size {size}";
                }
                $error = strtr($error, [
                    '{size}' => $maxSize
                ]);
                $messages[] = $error;
            }

            if ($this->allowedTypes && $isValid && !$this->validateFileType($value)){
                $availFileTypes = implode(',', $this->allowedTypes);
                if (isset($this->errors[self::UPLOAD_ERR_INCORRECT_TYPE])) {
                    $error = $this->errors[self::UPLOAD_ERR_INCORRECT_TYPE];
                } else {
                    $error = "Incorrect file type. Available {types}";
                }
                $error = strtr($error, [
                    '{types}' => $availFileTypes
                ]);
                $messages[] = $error;
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