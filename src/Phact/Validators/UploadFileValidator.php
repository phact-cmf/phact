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
use Phact\Translate\Translator;

class UploadFileValidator extends FormFieldValidator
{
    use Translator;

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
            UPLOAD_ERR_INI_SIZE => self::t('Phact.validators', 'The uploaded file is larger than the available size'),
            UPLOAD_ERR_FORM_SIZE => self::t('Phact.validators', 'The uploaded file is larger than the available size'),
            UPLOAD_ERR_PARTIAL => self::t('Phact.validators', 'The file was partially uploaded, please try again'),
            UPLOAD_ERR_NO_FILE => self::t('Phact.validators', 'The file was not uploaded'),
            UPLOAD_ERR_NO_TMP_DIR => self::t('Phact.validators', 'Internal upload error (no temporary folder)'),
            UPLOAD_ERR_CANT_WRITE => self::t('Phact.validators', 'Internal upload error (disk not available for writing)'),
            UPLOAD_ERR_EXTENSION => self::t('Phact.validators', 'Internal upload error'),
            self::UPLOAD_ERR_UNDEFINED => self::t('Phact.validators', 'Unknown upload error'),
            self::UPLOAD_ERR_MAX_SIZE => self::t('Phact.validators', 'The uploaded file is larger than the available size. Maximum file size is {size} bytes'),
            self::UPLOAD_ERR_INCORRECT_TYPE => self::t('Phact.validators', 'Invalid file type. Available file types: {types}'),
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
        return self::t('Phact.validators', 'Upload error');
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
                    $error = self::t('Phact.validators', 'The uploaded file is larger than the available size. Maximum file size is {size} bytes');
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
                    $error = self::t('Phact.validators', 'Invalid file type. Available file types: {types}');
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

        if (!in_array(mb_strtolower($ext), $this->allowedTypes)){
            return false;
        }
        return true;

    }
}