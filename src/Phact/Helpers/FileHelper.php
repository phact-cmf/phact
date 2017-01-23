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
    /**
     * @param $bytes
     * @param int $precision
     * @return string
     */
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

    /**
     * Locale-safe pathinfo
     * @param $path
     * @param null $options
     * @return array
     */
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
     * @param $mimeTypes
     * @return mixed
     */
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
