<?php
/**
 * Created by PhpStorm.
 * User: aleksandrgordeev
 * Date: 08.08.16
 * Time: 17:02
 */

namespace Phact\Helpers;


class FileHelper
{
    public static function fixMultiFile(array $files = [], $removeEmpty = true)
    {
        if ($files == null) {
            $files = is_array($_FILES) ? $_FILES : array();
        }

        //make there there is a file, and see if the first item is also an array
        $newFiles = array();
        foreach ($files as $name => $attributes) {
            if (is_array(reset($attributes))) { //check first item
                foreach ($attributes as $attribute => $item) { //array file submit, eg name="model[file]"
                    foreach ($item as $key => $value) {
                        if (is_array($value)) {
                            foreach ($value as $key2 => $sub) { // multi-array file submit, eg name="model[file][]"
                                $newFiles[$name][$key][$key2][$attribute] = $sub;
                            }
                        } else {
                            $newFiles[$name][$key][$attribute] = $value;
                        }
                    }
                }
            } else { // regular file submit, eg name="file"
                $newFiles[$name] = $attributes;
            }
        }

        return $removeEmpty ? self::removeEmptyFiles($newFiles) : $newFiles;
    }

    private static function removeEmptyFiles(array $files, $r = [], $level = 1)
    {
        foreach ($files as $key => $value) {
            if (is_array($value)) {
                if (isset($value['error']) && $value['error'] == UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $level++;
                $value = self::removeEmptyFiles($value, $r, $level);
            }
            $r[$key] = $value;
        }
        return $r;
    }

    /**
     * Original code: https://gist.github.com/lorenzos/1711e81a9162320fde20
     * Article: http://stackoverflow.com/questions/15025875/what-is-the-best-way-in-php-to-read-last-lines-from-a-file
     *
     * @param $filepath
     * @param int $lines
     * @param bool $adaptive
     * @return bool|string
     */
    public function readFile($filepath, $lines = 1, $adaptive = true)
    {
        // Open file
        $f = @fopen($filepath, "rb");
        if ($f === false) {
            return false;
        }

        // Sets buffer size
        if (!$adaptive) {
            $buffer = 4096;
        } else {
            $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
        }

        // Jump to last character
        fseek($f, -1, SEEK_END);

        // Read it and adjust line number if necessary
        // (Otherwise the result would be wrong if file doesn't end with a blank line)
        if (fread($f, 1) != "\n") {
            $lines -= 1;
        }

        // Start reading
        $output = '';
        $chunk = '';

        // While we would like more
        while (ftell($f) > 0 && $lines >= 0) {
            // Figure out how far back we should jump
            $seek = min(ftell($f), $buffer);
            // Do the jump (backwards, relative to where we are)
            fseek($f, -$seek, SEEK_CUR);
            // Read a chunk and prepend it to our output
            $output = ($chunk = fread($f, $seek)) . $output;
            // Jump back to where we started reading
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            // Decrease our line counter
            $lines -= substr_count($chunk, "\n");
        }

        // While we have too many lines
        // (Because of buffer size we might have read too many)
        while ($lines++ < 0) {
            // Find first newline and remove all text before that
            $output = substr($output, strpos($output, "\n") + 1);
        }

        // Close file and return
        fclose($f);
        return trim($output);
    }

    /**
     * Returns the extension name of a file path.
     * For example, the path "path/to/something.php" would return "php".
     * @param string $path the file path
     * @return string the extension name without the dot character.
     * @since 1.1.2
     */
    public static function getExtension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Copies a directory recursively as another.
     * If the destination directory does not exist, it will be created recursively.
     * @param string $src the source directory
     * @param string $dst the destination directory
     * @param array $options options for directory copy. Valid options are:
     * <ul>
     * <li>fileTypes: array, list of file name suffix (without dot). Only files with these suffixes will be copied.</li>
     * <li>exclude: array, list of directory and file exclusions. Each exclusion can be either a name or a path.
     * If a file or directory name or path matches the exclusion, it will not be copied. For example, an exclusion of
     * '.svn' will exclude all files and directories whose name is '.svn'. And an exclusion of '/a/b' will exclude
     * file or directory '$src/a/b'. Note, that '/' should be used as separator regardless of the value of the DIRECTORY_SEPARATOR constant.
     * </li>
     * <li>level: integer, recursion depth, default=-1.
     * Level -1 means copying all directories and files under the directory;
     * Level 0 means copying only the files DIRECTLY under the directory;
     * level N means copying those directories that are within N levels.
     * </li>
     * <li>newDirMode - the permission to be set for newly copied directories (defaults to 0777);</li>
     * <li>newFileMode - the permission to be set for newly copied files (defaults to the current environment setting).</li>
     * </ul>
     */
    public static function copyDirectory($src, $dst, $options = array())
    {
        $fileTypes = array();
        $exclude = array();
        $level = -1;
        extract($options);
        if (!is_dir($dst)) {
            self::createDirectory($dst, isset($options['newDirMode']) ? $options['newDirMode'] : null, true);
        }

        self::copyDirectoryRecursive($src, $dst, '', $fileTypes, $exclude, $level, $options);
    }

    /**
     * Removes a directory recursively.
     * @param string $directory to be deleted recursively.
     * @since 1.1.14
     */
    public static function removeDirectory($directory)
    {
        $items = glob($directory . DIRECTORY_SEPARATOR . '{,.}*', GLOB_MARK | GLOB_BRACE);
        foreach ($items as $item) {
            if (basename($item) == '.' || basename($item) == '..') {
                continue;
            }
            if (substr($item, -1) == DIRECTORY_SEPARATOR) {
                self::removeDirectory($item);
            } else {
                unlink($item);
            }
        }

        if (is_dir($directory)) {
            rmdir($directory);
        }
    }

    /**
     * Returns the files found under the specified directory and subdirectories.
     * @param string $dir the directory under which the files will be looked for
     * @param array $options options for file searching. Valid options are:
     * <ul>
     * <li>fileTypes: array, list of file name suffix (without dot). Only files with these suffixes will be returned.</li>
     * <li>exclude: array, list of directory and file exclusions. Each exclusion can be either a name or a path.
     * If a file or directory name or path matches the exclusion, it will not be copied. For example, an exclusion of
     * '.svn' will exclude all files and directories whose name is '.svn'. And an exclusion of '/a/b' will exclude
     * file or directory '$src/a/b'. Note, that '/' should be used as separator regardless of the value of the DIRECTORY_SEPARATOR constant.
     * </li>
     * <li>level: integer, recursion depth, default=-1.
     * Level -1 means searching for all directories and files under the directory;
     * Level 0 means searching for only the files DIRECTLY under the directory;
     * level N means searching for those directories that are within N levels.
     * </li>
     * <li>absolutePaths: boolean, whether to return absolute paths or relative ones, defaults to true.</li>
     * </ul>
     * @return array files found under the directory. The file list is sorted.
     */
    public static function findFiles($dir, $options = array())
    {
        $fileTypes = array();
        $exclude = array();
        $level = -1;
        $absolutePaths = true;
        extract($options);
        $list = self::findFilesRecursive($dir, '', $fileTypes, $exclude, $level, $absolutePaths);
        sort($list);
        return $list;
    }

    /**
     * Copies a directory.
     * This method is mainly used by {@link copyDirectory}.
     * @param string $src the source directory
     * @param string $dst the destination directory
     * @param string $base the path relative to the original source directory
     * @param array $fileTypes list of file name suffix (without dot). Only files with these suffixes will be copied.
     * @param array $exclude list of directory and file exclusions. Each exclusion can be either a name or a path.
     * If a file or directory name or path matches the exclusion, it will not be copied. For example, an exclusion of
     * '.svn' will exclude all files and directories whose name is '.svn'. And an exclusion of '/a/b' will exclude
     * file or directory '$src/a/b'. Note, that '/' should be used as separator regardless of the value of the DIRECTORY_SEPARATOR constant.
     * @param integer $level recursion depth. It defaults to -1.
     * Level -1 means copying all directories and files under the directory;
     * Level 0 means copying only the files DIRECTLY under the directory;
     * level N means copying those directories that are within N levels.
     * @param array $options additional options. The following options are supported:
     * newDirMode - the permission to be set for newly copied directories (defaults to 0777);
     * newFileMode - the permission to be set for newly copied files (defaults to the current environment setting).
     */
    protected static function copyDirectoryRecursive($src, $dst, $base, $fileTypes, $exclude, $level, $options)
    {
        if (!is_dir($dst)) {
            self::createDirectory($dst, isset($options['newDirMode']) ? $options['newDirMode'] : null, false);
        }

        $folder = opendir($src);
        if ($folder === false) {
            throw new Exception('Unable to open directory: ' . $src);
        }
        while (($file = readdir($folder)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $src . DIRECTORY_SEPARATOR . $file;
            $isFile = is_file($path);
            if (self::validatePath($base, $file, $isFile, $fileTypes, $exclude)) {
                if ($isFile) {
                    copy($path, $dst . DIRECTORY_SEPARATOR . $file);
                    if (isset($options['newFileMode'])) {
                        @chmod($dst . DIRECTORY_SEPARATOR . $file, $options['newFileMode']);
                    }
                } elseif ($level) {
                    self::copyDirectoryRecursive($path, $dst . DIRECTORY_SEPARATOR . $file, $base . '/' . $file, $fileTypes, $exclude, $level - 1, $options);
                }
            }
        }
        closedir($folder);
    }

    /**
     * Returns the files found under the specified directory and subdirectories.
     * This method is mainly used by {@link findFiles}.
     * @param string $dir the source directory
     * @param string $base the path relative to the original source directory
     * @param array $fileTypes list of file name suffix (without dot). Only files with these suffixes will be returned.
     * @param array $exclude list of directory and file exclusions. Each exclusion can be either a name or a path.
     * If a file or directory name or path matches the exclusion, it will not be copied. For example, an exclusion of
     * '.svn' will exclude all files and directories whose name is '.svn'. And an exclusion of '/a/b' will exclude
     * file or directory '$src/a/b'. Note, that '/' should be used as separator regardless of the value of the DIRECTORY_SEPARATOR constant.
     * @param integer $level recursion depth. It defaults to -1.
     * Level -1 means searching for all directories and files under the directory;
     * Level 0 means searching for only the files DIRECTLY under the directory;
     * level N means searching for those directories that are within N levels.
     * @param boolean $absolutePaths whether to return absolute paths or relative ones
     * @throws \Mindy\Exception\Exception
     * @return array files found under the directory.
     */
    protected static function findFilesRecursive($dir, $base, $fileTypes, $exclude, $level, $absolutePaths)
    {
        $list = array();
        $handle = opendir($dir . $base);
        if ($handle === false) {
            throw new Exception('Unable to open directory: ' . $dir);
        }
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = substr($base . DIRECTORY_SEPARATOR . $file, 1);
            $fullPath = $dir . DIRECTORY_SEPARATOR . $path;
            $isFile = is_file($fullPath);
            if (self::validatePath($base, $file, $isFile, $fileTypes, $exclude)) {
                if ($isFile) {
                    $list[] = $absolutePaths ? $fullPath : $path;
                } elseif ($level) {
                    $list = array_merge($list, self::findFilesRecursive($dir, $base . '/' . $file, $fileTypes, $exclude, $level - 1, $absolutePaths));
                }
            }
        }
        closedir($handle);
        return $list;
    }

    /**
     * Validates a file or directory.
     * @param string $base the path relative to the original source directory
     * @param string $file the file or directory name
     * @param boolean $isFile whether this is a file
     * @param array $fileTypes list of valid file name suffixes (without dot).
     * @param array $exclude list of directory and file exclusions. Each exclusion can be either a name or a path.
     * If a file or directory name or path matches the exclusion, false will be returned. For example, an exclusion of
     * '.svn' will return false for all files and directories whose name is '.svn'. And an exclusion of '/a/b' will return false for
     * file or directory '$src/a/b'. Note, that '/' should be used as separator regardless of the value of the DIRECTORY_SEPARATOR constant.
     * @return boolean whether the file or directory is valid
     */
    protected static function validatePath($base, $file, $isFile, $fileTypes, $exclude)
    {
        foreach ($exclude as $e) {
            if ($file === $e || strpos($base . '/' . $file, $e) === 0) {
                return false;
            }
        }
        if (!$isFile || empty($fileTypes)) {
            return true;
        }
        if (($type = pathinfo($file, PATHINFO_EXTENSION)) !== '') {
            return in_array($type, $fileTypes);
        } else {
            return false;
        }
    }

    /**
     * Determines the MIME type of the specified file.
     * This method will attempt the following approaches in order:
     * <ol>
     * <li>finfo</li>
     * <li>mime_content_type</li>
     * <li>{@link getMimeTypeByExtension}, when $checkExtension is set true.</li>
     * </ol>
     * @param string $file the file name.
     * @param string $magicFile name of a magic database file, usually something like /path/to/magic.mime.
     * This will be passed as the second parameter to {@link http://php.net/manual/en/function.finfo-open.php finfo_open}.
     * Magic file format described in {@link http://linux.die.net/man/5/magic man 5 magic}, note that this file does not
     * contain a standard PHP array as you might suppose. Specified magic file will be used only when fileinfo
     * PHP extension is available. This parameter has been available since version 1.1.3.
     * @param boolean $checkExtension whether to check the file extension in case the MIME type cannot be determined
     * based on finfo and mime_content_type. Defaults to true. This parameter has been available since version 1.1.4.
     * @return string the MIME type. Null is returned if the MIME type cannot be determined.
     */
    public static function getMimeType($file, $magicFile = null, $checkExtension = true)
    {
        if (function_exists('finfo_open')) {
            $options = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            $info = $magicFile === null ? finfo_open($options) : finfo_open($options, $magicFile);

            if ($info && ($result = finfo_file($info, $file)) !== false) {
                return $result;
            }
        }

        if (function_exists('mime_content_type') && ($result = mime_content_type($file)) !== false) {
            return $result;
        }

        return $checkExtension ? self::getMimeTypeByExtension($file) : null;
    }

    public static function bytesToSize($bytes, $precision = 2)
    {
        $kilobyte = 1024;
        $megabyte = $kilobyte * 1024;
        $gigabyte = $megabyte * 1024;
        $terabyte = $gigabyte * 1024;

        if (($bytes >= 0) && ($bytes < $kilobyte)) {
            return $bytes . ' B';

        } elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
            return round($bytes / $kilobyte, $precision) . ' KB';

        } elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
            return round($bytes / $megabyte, $precision) . ' MB';

        } elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
            return round($bytes / $gigabyte, $precision) . ' GB';

        } elseif ($bytes >= $terabyte) {
            return round($bytes / $terabyte, $precision) . ' TB';
        } else {
            return $bytes . ' B';
        }
    }

    /**
     * Determines the MIME type based on the extension name of the specified file.
     * This method will use a local map between extension name and MIME type.
     * @param string $file the file name.
     * @param string $magicFile the path of the file that contains all available MIME type information.
     * If this is not set, the default 'system.utils.mimeTypes' file will be used.
     * This parameter has been available since version 1.1.3.
     * @return string the MIME type. Null is returned if the MIME type cannot be determined.
     */
    public static function getMimeTypeByFileName($file, $magicFile = null)
    {
        static $extensions, $customExtensions = [];
        if ($magicFile === null && $extensions === null) {
            $extensions = self::$mimeTypes;
        } elseif ($magicFile !== null && !isset($customExtensions[$magicFile])) {
            $customExtensions[$magicFile] = require($magicFile);
        }
        if (($ext = pathinfo($file, PATHINFO_EXTENSION)) !== '') {
            $ext = strtolower($ext);
            if ($magicFile === null && isset($extensions[$ext])) {
                return $extensions[$ext];
            } elseif ($magicFile !== null && isset($customExtensions[$magicFile][$ext])) {
                return $customExtensions[$magicFile][$ext];
            }
        }
        return null;
    }

    /**
     * Determines the MIME type based on the extension name of the specified file.
     * This method will use a local map between extension name and MIME type.
     * @param string $file the file name.
     * @param string $magicFile the path of the file that contains all available MIME type information.
     * If this is not set, the default 'system.utils.mimeTypes' file will be used.
     * This parameter has been available since version 1.1.3.
     * @return string the MIME type. Null is returned if the MIME type cannot be determined.
     */
    public static function getMimeTypeByExtension($ext, $magicFile = null)
    {
        static $extensions, $customExtensions = [];
        if ($magicFile === null && $extensions === null) {
            $extensions = self::$mimeTypes;
        } elseif ($magicFile !== null && !isset($customExtensions[$magicFile])) {
            $customExtensions[$magicFile] = require($magicFile);
        }
        $ext = strtolower($ext);
        if ($magicFile === null && isset($extensions[$ext])) {
            return $extensions[$ext];
        } elseif ($magicFile !== null && isset($customExtensions[$magicFile][$ext])) {
            return $customExtensions[$magicFile][$ext];
        }
        return null;
    }

    /**
     * Shared environment safe version of mkdir. Supports recursive creation.
     * For avoidance of umask side-effects chmod is used.
     *
     * @param string $dst path to be created
     * @param integer $mode the permission to be set for newly created directories, if not set - 0777 will be used
     * @param boolean $recursive whether to create directory structure recursive if parent dirs do not exist
     * @return boolean result of mkdir
     * @see mkdir
     */
    public static function createDirectory($dst, $mode = null, $recursive = false)
    {
        if ($mode === null) {
            $mode = 0777;
        }
        $prevDir = dirname($dst);
        if ($recursive && !is_dir($dst) && !is_dir($prevDir)) {
            self::createDirectory(dirname($dst), $mode, true);
        }
        $res = mkdir($dst, $mode);
        @chmod($dst, $mode);
        return $res;
    }

    /**
     * Locale-safe basename
     * @param $filename
     * @return string
     */
    public static function mbBasename($filename)
    {
        $path = rtrim($filename, DIRECTORY_SEPARATOR);
        $lastSeparator = mb_strrpos($path, DIRECTORY_SEPARATOR, 0, 'UTF-8');
        $lastSeparator = $lastSeparator === false ? 0 : $lastSeparator + 1;
        return mb_substr($path, $lastSeparator, null, 'UTF-8');
    }

    public static function mbPathinfo($path, $options = null)
    {
        $info = array('dirname' => '', 'basename' => '', 'extension' => '', 'filename' => '');
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $lastSeparator = mb_strrpos($path, DIRECTORY_SEPARATOR, 0, 'UTF-8');
        if ($lastSeparator !== false) {
            $info['basename'] = mb_substr($path, $lastSeparator + 1, null, 'UTF-8');
            $info['dirname'] = mb_substr($path, 0, $lastSeparator, 'UTF-8');
        } else {
            $info['basename'] = $path;
        }

        $lastDot = mb_strrpos($info['basename'], '.', 0, 'UTF-8');
        if ($lastDot !== false) {
            $info['extension'] = mb_substr($info['basename'], $lastDot + 1, null, 'UTF-8');
            $info['filename'] = mb_substr($info['basename'], 0, $lastDot, 'UTF-8');
        } else {
            $info['filename'] = $info['basename'];
        }

        switch ($options) {
            case PATHINFO_DIRNAME:
            case 'dirname':
                return $info['dirname'];
                break;
            case PATHINFO_BASENAME:
            case 'basename':
                return $info['basename'];
                break;
            case PATHINFO_EXTENSION:
            case 'extension':
                return $info['extension'];
                break;
            case PATHINFO_FILENAME:
            case 'filename':
                return $info['filename'];
                break;
            default:
                return $info;
        }
    }


    public static function getExtensionsFromMimes($mimeTypes)
    {
        $types = [];
        foreach($mimeTypes as $type => $mime){
            preg_match('/^(.+)+\/+\*/', $mime, $hasMultipleTypes);

            if (isset($hasMultipleTypes[1])){

                foreach(self::$mimeTypes as $fileExt => $staticType){
                    if (0 === strpos($staticType, $hasMultipleTypes[1])) {
                        $types[] = $fileExt;
                    }
                }
            }elseif (isset(self::$mimeTypes[$mime])){
                $types[] = $type;
            }

        }
        return array_unique($types);
    }

    public static $mimeTypes = array(
        'ai' => 'application/postscript',
        'aif' => 'audio/x-aiff',
        'aifc' => 'audio/x-aiff',
        'aiff' => 'audio/x-aiff',
        'anx' => 'application/annodex',
        'asc' => 'text/plain',
        'au' => 'audio/basic',
        'avi' => 'video/x-msvideo',
        'axa' => 'audio/annodex',
        'axv' => 'video/annodex',
        'bcpio' => 'application/x-bcpio',
        'bin' => 'application/octet-stream',
        'bmp' => 'image/bmp',
        'c' => 'text/plain',
        'cc' => 'text/plain',
        'ccad' => 'application/clariscad',
        'cdf' => 'application/x-netcdf',
        'class' => 'application/octet-stream',
        'cpio' => 'application/x-cpio',
        'cpt' => 'application/mac-compactpro',
        'csh' => 'application/x-csh',
        'css' => 'text/css',
        'csv' => 'text/csv',
        'dcr' => 'application/x-director',
        'dir' => 'application/x-director',
        'dms' => 'application/octet-stream',
        'doc' => 'application/msword',
        'drw' => 'application/drafting',
        'dvi' => 'application/x-dvi',
        'dwg' => 'application/acad',
        'dxf' => 'application/dxf',
        'dxr' => 'application/x-director',
        'eps' => 'application/postscript',
        'etx' => 'text/x-setext',
        'exe' => 'application/octet-stream',
        'ez' => 'application/andrew-inset',
        'f' => 'text/plain',
        'f90' => 'text/plain',
        'flac' => 'audio/flac',
        'fli' => 'video/x-fli',
        'flv' => 'video/x-flv',
        'gif' => 'image/gif',
        'gtar' => 'application/x-gtar',
        'gz' => 'application/x-gzip',
        'h' => 'text/plain',
        'hdf' => 'application/x-hdf',
        'hh' => 'text/plain',
        'hqx' => 'application/mac-binhex40',
        'htm' => 'text/html',
        'html' => 'text/html',
        'ice' => 'x-conference/x-cooltalk',
        'ief' => 'image/ief',
        'iges' => 'model/iges',
        'igs' => 'model/iges',
        'ips' => 'application/x-ipscript',
        'ipx' => 'application/x-ipix',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'application/x-javascript',
        'kar' => 'audio/midi',
        'latex' => 'application/x-latex',
        'lha' => 'application/octet-stream',
        'lsp' => 'application/x-lisp',
        'lzh' => 'application/octet-stream',
        'm' => 'text/plain',
        'man' => 'application/x-troff-man',
        'me' => 'application/x-troff-me',
        'mesh' => 'model/mesh',
        'mid' => 'audio/midi',
        'midi' => 'audio/midi',
        'mif' => 'application/vnd.mif',
        'mime' => 'www/mime',
        'mov' => 'video/quicktime',
        'movie' => 'video/x-sgi-movie',
        'mp2' => 'audio/mpeg',
        'mp3' => 'audio/mpeg',
        'mpe' => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mpga' => 'audio/mpeg',
        'ms' => 'application/x-troff-ms',
        'msh' => 'model/mesh',
        'nc' => 'application/x-netcdf',
        'oga' => 'audio/ogg',
        'ogg' => 'audio/ogg',
        'ogv' => 'video/ogg',
        'ogx' => 'application/ogg',
        'oda' => 'application/oda',
        'pbm' => 'image/x-portable-bitmap',
        'pdb' => 'chemical/x-pdb',
        'pdf' => 'application/pdf',
        'pgm' => 'image/x-portable-graymap',
        'pgn' => 'application/x-chess-pgn',
        'png' => 'image/png',
        'pnm' => 'image/x-portable-anymap',
        'pot' => 'application/mspowerpoint',
        'ppm' => 'image/x-portable-pixmap',
        'pps' => 'application/mspowerpoint',
        'ppt' => 'application/mspowerpoint',
        'ppz' => 'application/mspowerpoint',
        'pre' => 'application/x-freelance',
        'prt' => 'application/pro_eng',
        'ps' => 'application/postscript',
        'qt' => 'video/quicktime',
        'ra' => 'audio/x-realaudio',
        'ram' => 'audio/x-pn-realaudio',
        'ras' => 'image/cmu-raster',
        'rgb' => 'image/x-rgb',
        'rm' => 'audio/x-pn-realaudio',
        'roff' => 'application/x-troff',
        'rpm' => 'audio/x-pn-realaudio-plugin',
        'rtf' => 'text/rtf',
        'rtx' => 'text/richtext',
        'scm' => 'application/x-lotusscreencam',
        'set' => 'application/set',
        'sgm' => 'text/sgml',
        'sgml' => 'text/sgml',
        'sh' => 'application/x-sh',
        'shar' => 'application/x-shar',
        'silo' => 'model/mesh',
        'sit' => 'application/x-stuffit',
        'skd' => 'application/x-koan',
        'skm' => 'application/x-koan',
        'skp' => 'application/x-koan',
        'skt' => 'application/x-koan',
        'smi' => 'application/smil',
        'smil' => 'application/smil',
        'snd' => 'audio/basic',
        'sol' => 'application/solids',
        'spl' => 'application/x-futuresplash',
        'spx' => 'audio/ogg',
        'src' => 'application/x-wais-source',
        'step' => 'application/STEP',
        'stl' => 'application/SLA',
        'stp' => 'application/STEP',
        'sv4cpio' => 'application/x-sv4cpio',
        'sv4crc' => 'application/x-sv4crc',
        'swf' => 'application/x-shockwave-flash',
        't' => 'application/x-troff',
        'tar' => 'application/x-tar',
        'tcl' => 'application/x-tcl',
        'tex' => 'application/x-tex',
        'texi' => 'application/x-texinfo',
        'texinfo' => 'application/x-texinfo',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'tr' => 'application/x-troff',
        'tsi' => 'audio/TSP-audio',
        'tsp' => 'application/dsptype',
        'tsv' => 'text/tab-separated-values',
        'txt' => 'text/plain',
        'unv' => 'application/i-deas',
        'ustar' => 'application/x-ustar',
        'vcd' => 'application/x-cdlink',
        'vda' => 'application/vda',
        'viv' => 'video/vnd.vivo',
        'vivo' => 'video/vnd.vivo',
        'vrml' => 'model/vrml',
        'wav' => 'audio/x-wav',
        'wrl' => 'model/vrml',
        'xbm' => 'image/x-xbitmap',
        'xlc' => 'application/vnd.ms-excel',
        'xll' => 'application/vnd.ms-excel',
        'xlm' => 'application/vnd.ms-excel',
        'xls' => 'application/vnd.ms-excel',
        'xlw' => 'application/vnd.ms-excel',
        'xml' => 'application/xml',
        'xpm' => 'image/x-xpixmap',
        'xspf' => 'application/xspf+xml',
        'xwd' => 'image/x-xwindowdump',
        'xyz' => 'chemical/x-pdb',
        'zip' => 'application/zip',
        'zipm' => 'multipart/x-zip',
    );
}
