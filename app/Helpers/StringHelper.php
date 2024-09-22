<?php

namespace App\Helpers;

use App\Models\Macros;
use Illuminate\Support\Str;
use Illuminate\Support\Number;

class StringHelper
{
    /**
     * @param int $max
     * @return null|string
     */
    public static function randomText(int $max = 6): null|string
    {
        $chars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
        $size = strlen($chars) - 1;
        $text = null;

        while ($max--)
            $text .= $chars[rand(0, $size)];

        return $text;
    }

    /**
     * @return string
     */
    public static function token(): string
    {
        return md5(Str::random(30));
    }

    /**
     * @param string $str
     * @param int $chars
     * @return string
     */
    public static function shortText(string $str, int $chars = 500): string
    {
        $pos = strpos(substr($str, $chars), " ");
        $srttmpend = strlen($str) > $chars ? '...' : '';

        return substr($str, 0, $chars + $pos) . ($srttmpend ?? '');
    }

    /**
     * @return int
     */
    public static function detectMaxUploadFileSize(): int
    {
        /**
         * Converts shorthands like "2M" or "512K" to bytes
         *
         * @param $size
         * @return false|float|int
         */
        $normalize = function ($size) {
            if (preg_match('/^(-?[\d\.]+)(|[KMG])$/i', $size, $match)) {
                $pos = array_search($match[2], ["", "K", "M", "G"]);
                $size = $match[1] * pow(1024, $pos);
            } else {
                return false;
            }
            return $size;
        };
        $limits = [];
        $limits[] = $normalize(ini_get('upload_max_filesize'));
        if (($max_post = $normalize(ini_get('post_max_size'))) != 0) {
            $limits[] = $max_post;
        }
        if (($memory_limit = $normalize(ini_get('memory_limit'))) != -1) {
            $limits[] = $memory_limit;
        }
        $maxFileSize = min($limits);

        return (int)$maxFileSize;
    }

    /**
     * @return string
     */
    public static function maxUploadFileSize(): string
    {
        $maxUploadFileSize = self::detectMaxUploadFileSize();

        if (!$maxUploadFileSize or $maxUploadFileSize == 0) {
            $maxUploadFileSize = 2097152;
        }

        return Number::fileSize($maxUploadFileSize);
    }

    /**
     * @param string $email
     * @return bool
     */
    public static function isEmail(string $email): bool
    {
        if (preg_match("/^([a-z0-9_\.\-]{1,70})@([a-z0-9\.\-]{1,70})\.([a-z]{2,12})$/i", $email))
            return true;
        else
            return false;
    }

    /**
     * @param string $ext
     * @return string
     */
    public static function getMimeType(string $ext): string
    {
        $mimetypes = [
            "123" => "application/vnd.lotus-1-2-3",
            "3ds" => "image/x-3ds",
            "669" => "audio/x-mod",
            "a" => "application/x-archive",
            "abw" => "application/x-abiword",
            "ac3" => "audio/ac3",
            "adb" => "text/x-adasrc",
            "ads" => "text/x-adasrc",
            "afm" => "application/x-font-afm",
            "ag" => "image/x-applix-graphics",
            "ai" => "application/illustrator",
            "aif" => "audio/x-aiff",
            "aifc" => "audio/x-aiff",
            "aiff" => "audio/x-aiff",
            "al" => "application/x-perl",
            "arj" => "application/x-arj",
            "as" => "application/x-applix-spreadsheet",
            "asc" => "text/plain",
            "asf" => "video/x-ms-asf",
            "asp" => "application/x-asp",
            "asx" => "video/x-ms-asf",
            "au" => "audio/basic",
            "avi" => "video/x-msvideo",
            "aw" => "application/x-applix-word",
            "bak" => "application/x-trash",
            "bcpio" => "application/x-bcpio",
            "bdf" => "application/x-font-bdf",
            "bib" => "text/x-bibtex",
            "bin" => "application/octet-stream",
            "blend" => "application/x-blender",
            "blender" => "application/x-blender",
            "bmp" => "image/bmp",
            "bz" => "application/x-bzip",
            "bz2" => "application/x-bzip",
            "c" => "text/x-csrc",
            "c++" => "text/x-c++src",
            "cc" => "text/x-c++src",
            "cdf" => "application/x-netcdf",
            "cdr" => "application/vnd.corel-draw",
            "cer" => "application/x-x509-ca-cert",
            "cert" => "application/x-x509-ca-cert",
            "cgi" => "application/x-cgi",
            "cgm" => "image/cgm",
            "chrt" => "application/x-kchart",
            "class" => "application/x-java",
            "cls" => "text/x-tex",
            "cpio" => "application/x-cpio",
            "cpp" => "text/x-c++src",
            "crt" => "application/x-x509-ca-cert",
            "cs" => "text/x-csharp",
            "csh" => "application/x-shellscript",
            "css" => "text/css",
            "cssl" => "text/css",
            "csv" => "text/x-comma-separated-values",
            "cur" => "image/x-win-bitmap",
            "cxx" => "text/x-c++src",
            "dat" => "video/mpeg",
            "dbf" => "application/x-dbase",
            "dc" => "application/x-dc-rom",
            "dcl" => "text/x-dcl",
            "dcm" => "image/x-dcm",
            "deb" => "application/x-deb",
            "der" => "application/x-x509-ca-cert",
            "desktop" => "application/x-desktop",
            "dia" => "application/x-dia-diagram",
            "diff" => "text/x-patch",
            "djv" => "image/vnd.djvu",
            "djvu" => "image/vnd.djvu",
            "doc" => "application/vnd.ms-word",
            "dsl" => "text/x-dsl",
            "dtd" => "text/x-dtd",
            "dvi" => "application/x-dvi",
            "dwg" => "image/vnd.dwg",
            "dxf" => "image/vnd.dxf",
            "egon" => "application/x-egon",
            "el" => "text/x-emacs-lisp",
            "eps" => "image/x-eps",
            "epsf" => "image/x-eps",
            "epsi" => "image/x-eps",
            "etheme" => "application/x-e-theme",
            "etx" => "text/x-setext",
            "exe" => "application/x-ms-dos-executable",
            "ez" => "application/andrew-inset",
            "f" => "text/x-fortran",
            "fig" => "image/x-xfig",
            "fits" => "image/x-fits",
            "flac" => "audio/x-flac",
            "flc" => "video/x-flic",
            "fli" => "video/x-flic",
            "flw" => "application/x-kivio",
            "fo" => "text/x-xslfo",
            "g3" => "image/fax-g3",
            "gb" => "application/x-gameboy-rom",
            "gcrd" => "text/x-vcard",
            "gen" => "application/x-genesis-rom",
            "gg" => "application/x-sms-rom",
            "gif" => "image/gif",
            "glade" => "application/x-glade",
            "gmo" => "application/x-gettext-translation",
            "gnc" => "application/x-gnucash",
            "gnucash" => "application/x-gnucash",
            "gnumeric" => "application/x-gnumeric",
            "gra" => "application/x-graphite",
            "gsf" => "application/x-font-type1",
            "gtar" => "application/x-gtar",
            "gz" => "application/x-gzip",
            "h" => "text/x-chdr",
            "h++" => "text/x-chdr",
            "hdf" => "application/x-hdf",
            "hh" => "text/x-c++hdr",
            "hp" => "text/x-chdr",
            "hpgl" => "application/vnd.hp-hpgl",
            "hs" => "text/x-haskell",
            "htm" => "text/html",
            "html" => "text/html",
            "icb" => "image/x-icb",
            "ico" => "image/x-ico",
            "ics" => "text/calendar",
            "idl" => "text/x-idl",
            "ief" => "image/ief",
            "iff" => "image/x-iff",
            "ilbm" => "image/x-ilbm",
            "iso" => "application/x-cd-image",
            "it" => "audio/x-it",
            "jar" => "application/x-jar",
            "java" => "text/x-java",
            "jng" => "image/x-jng",
            "jp2" => "image/jpeg2000",
            "jpe" => "image/jpeg",
            "jpeg" => "image/jpeg",
            "jpg" => "image/jpeg",
            "jpr" => "application/x-jbuilder-project",
            "jpx" => "application/x-jbuilder-project",
            "js" => "application/x-javascript",
            "karbon" => "application/x-karbon",
            "kdelnk" => "application/x-desktop",
            "kfo" => "application/x-kformula",
            "kil" => "application/x-killustrator",
            "kon" => "application/x-kontour",
            "kpm" => "application/x-kpovmodeler",
            "kpr" => "application/x-kpresenter",
            "kpt" => "application/x-kpresenter",
            "kra" => "application/x-krita",
            "ksp" => "application/x-kspread",
            "kud" => "application/x-kugar",
            "kwd" => "application/x-kword",
            "kwt" => "application/x-kword",
            "la" => "application/x-shared-library-la",
            "lha" => "application/x-lha",
            "lhs" => "text/x-literate-haskell",
            "lhz" => "application/x-lhz",
            "log" => "text/x-log",
            "ltx" => "text/x-tex",
            "lwo" => "image/x-lwo",
            "lwob" => "image/x-lwo",
            "lws" => "image/x-lws",
            "lyx" => "application/x-lyx",
            "lzh" => "application/x-lha",
            "lzo" => "application/x-lzop",
            "m" => "text/x-objcsrc",
            "m15" => "audio/x-mod",
            "m3u" => "audio/x-mpegurl",
            "man" => "application/x-troff-man",
            "md" => "application/x-genesis-rom",
            "me" => "text/x-troff-me",
            "mgp" => "application/x-magicpoint",
            "mid" => "audio/midi",
            "midi" => "audio/midi",
            "mif" => "application/x-mif",
            "mkv" => "application/x-matroska",
            "mm" => "text/x-troff-mm",
            "mml" => "text/mathml",
            "mng" => "video/x-mng",
            "moc" => "text/x-moc",
            "mod" => "audio/x-mod",
            "moov" => "video/quicktime",
            "mov" => "video/quicktime",
            "movie" => "video/x-sgi-movie",
            "mp2" => "video/mpeg",
            "mp3" => "audio/x-mp3",
            "mpe" => "video/mpeg",
            "mpeg" => "video/mpeg",
            "mpg" => "video/mpeg",
            "ms" => "text/x-troff-ms",
            "msod" => "image/x-msod",
            "msx" => "application/x-msx-rom",
            "mtm" => "audio/x-mod",
            "n64" => "application/x-n64-rom",
            "nc" => "application/x-netcdf",
            "nes" => "application/x-nes-rom",
            "nsv" => "video/x-nsv",
            "o" => "application/x-object",
            "obj" => "application/x-tgif",
            "oda" => "application/oda",
            "ogg" => "application/ogg",
            "old" => "application/x-trash",
            "oleo" => "application/x-oleo",
            "p" => "text/x-pascal",
            "p12" => "application/x-pkcs12",
            "p7s" => "application/pkcs7-signature",
            "pas" => "text/x-pascal",
            "patch" => "text/x-patch",
            "pbm" => "image/x-portable-bitmap",
            "pcd" => "image/x-photo-cd",
            "pcf" => "application/x-font-pcf",
            "pcl" => "application/vnd.hp-pcl",
            "pdb" => "application/vnd.palm",
            "pdf" => "application/pdf",
            "pem" => "application/x-x509-ca-cert",
            "perl" => "application/x-perl",
            "pfa" => "application/x-font-type1",
            "pfb" => "application/x-font-type1",
            "pfx" => "application/x-pkcs12",
            "pgm" => "image/x-portable-graymap",
            "pgn" => "application/x-chess-pgn",
            "pgp" => "application/pgp",
            "php" => "application/x-php",
            "php3" => "application/x-php",
            "php4" => "application/x-php",
            "pict" => "image/x-pict",
            "pict1" => "image/x-pict",
            "pict2" => "image/x-pict",
            "pl" => "application/x-perl",
            "pls" => "audio/x-scpls",
            "pm" => "application/x-perl",
            "png" => "image/png",
            "pnm" => "image/x-portable-anymap",
            "po" => "text/x-gettext-translation",
            "pot" => "text/x-gettext-translation-template",
            "ppm" => "image/x-portable-pixmap",
            "pps" => "application/vnd.ms-powerpoint",
            "ppt" => "application/vnd.ms-powerpoint",
            "ppz" => "application/vnd.ms-powerpoint",
            "ps" => "application/postscript",
            "psd" => "image/x-psd",
            "psf" => "application/x-font-linux-psf",
            "psid" => "audio/prs.sid",
            "pw" => "application/x-pw",
            "py" => "application/x-python",
            "pyc" => "application/x-python-bytecode",
            "pyo" => "application/x-python-bytecode",
            "qif" => "application/x-qw",
            "qt" => "video/quicktime",
            "qtvr" => "video/quicktime",
            "ra" => "audio/x-pn-realaudio",
            "ram" => "audio/x-pn-realaudio",
            "rar" => "application/x-rar",
            "ras" => "image/x-cmu-raster",
            "rdf" => "text/rdf",
            "rej" => "application/x-reject",
            "rgb" => "image/x-rgb",
            "rle" => "image/rle",
            "rm" => "audio/x-pn-realaudio",
            "roff" => "application/x-troff",
            "rpm" => "application/x-rpm",
            "rss" => "text/rss",
            "rtf" => "application/rtf",
            "rtx" => "text/richtext",
            "s3m" => "audio/x-s3m",
            "sam" => "application/x-amipro",
            "scm" => "text/x-scheme",
            "sda" => "application/vnd.stardivision.draw",
            "sdc" => "application/vnd.stardivision.calc",
            "sdd" => "application/vnd.stardivision.impress",
            "sdp" => "application/vnd.stardivision.impress",
            "sds" => "application/vnd.stardivision.chart",
            "sdw" => "application/vnd.stardivision.writer",
            "sgi" => "image/x-sgi",
            "sgl" => "application/vnd.stardivision.writer",
            "sgm" => "text/sgml",
            "sgml" => "text/sgml",
            "sh" => "application/x-shellscript",
            "shar" => "application/x-shar",
            "siag" => "application/x-siag",
            "sid" => "audio/prs.sid",
            "sik" => "application/x-trash",
            "slk" => "text/spreadsheet",
            "smd" => "application/vnd.stardivision.mail",
            "smf" => "application/vnd.stardivision.math",
            "smi" => "application/smil",
            "smil" => "application/smil",
            "sml" => "application/smil",
            "sms" => "application/x-sms-rom",
            "snd" => "audio/basic",
            "so" => "application/x-sharedlib",
            "spd" => "application/x-font-speedo",
            "sql" => "text/x-sql",
            "src" => "application/x-wais-source",
            "stc" => "application/vnd.sun.xml.calc.template",
            "std" => "application/vnd.sun.xml.draw.template",
            "sti" => "application/vnd.sun.xml.impress.template",
            "stm" => "audio/x-stm",
            "stw" => "application/vnd.sun.xml.writer.template",
            "sty" => "text/x-tex",
            "sun" => "image/x-sun-raster",
            "sv4cpio" => "application/x-sv4cpio",
            "sv4crc" => "application/x-sv4crc",
            "svg" => "image/svg+xml",
            "swf" => "application/x-shockwave-flash",
            "sxc" => "application/vnd.sun.xml.calc",
            "sxd" => "application/vnd.sun.xml.draw",
            "sxg" => "application/vnd.sun.xml.writer.global",
            "sxi" => "application/vnd.sun.xml.impress",
            "sxm" => "application/vnd.sun.xml.math",
            "sxw" => "application/vnd.sun.xml.writer",
            "sylk" => "text/spreadsheet",
            "t" => "application/x-troff",
            "tar" => "application/x-tar",
            "tcl" => "text/x-tcl",
            "tcpalette" => "application/x-terminal-color-palette",
            "tex" => "text/x-tex",
            "texi" => "text/x-texinfo",
            "texinfo" => "text/x-texinfo",
            "tga" => "image/x-tga",
            "tgz" => "application/x-compressed-tar",
            "theme" => "application/x-theme",
            "tif" => "image/tiff",
            "tiff" => "image/tiff",
            "tk" => "text/x-tcl",
            "torrent" => "application/x-bittorrent",
            "tr" => "application/x-troff",
            "ts" => "application/x-linguist",
            "tsv" => "text/tab-separated-values",
            "ttf" => "application/x-font-ttf",
            "txt" => "text/plain",
            "tzo" => "application/x-tzo",
            "ui" => "application/x-designer",
            "uil" => "text/x-uil",
            "ult" => "audio/x-mod",
            "uni" => "audio/x-mod",
            "uri" => "text/x-uri",
            "url" => "text/x-uri",
            "ustar" => "application/x-ustar",
            "vcf" => "text/x-vcalendar",
            "vcs" => "text/x-vcalendar",
            "vct" => "text/x-vcard",
            "vob" => "video/mpeg",
            "voc" => "audio/x-voc",
            "vor" => "application/vnd.stardivision.writer",
            "vpp" => "application/x-extension-vpp",
            "wav" => "audio/x-wav",
            "wb1" => "application/x-quattropro",
            "wb2" => "application/x-quattropro",
            "wb3" => "application/x-quattropro",
            "wk1" => "application/vnd.lotus-1-2-3",
            "wk3" => "application/vnd.lotus-1-2-3",
            "wk4" => "application/vnd.lotus-1-2-3",
            "wks" => "application/vnd.lotus-1-2-3",
            "wmf" => "image/x-wmf",
            "wml" => "text/vnd.wap.wml",
            "wmv" => "video/x-ms-wmv",
            "wpd" => "application/vnd.wordperfect",
            "wpg" => "application/x-wpg",
            "wri" => "application/x-mswrite",
            "wrl" => "model/vrml",
            "xac" => "application/x-gnucash",
            "xbel" => "application/x-xbel",
            "xbm" => "image/x-xbitmap",
            "xcf" => "image/x-xcf",
            "xhtml" => "application/xhtml+xml",
            "xi" => "audio/x-xi",
            "xla" => "application/vnd.ms-excel",
            "xlc" => "application/vnd.ms-excel",
            "xld" => "application/vnd.ms-excel",
            "xll" => "application/vnd.ms-excel",
            "xlm" => "application/vnd.ms-excel",
            "xls" => "application/vnd.ms-excel",
            "xlt" => "application/vnd.ms-excel",
            "xlw" => "application/vnd.ms-excel",
            "xm" => "audio/x-xm",
            "xmi" => "text/x-xmi",
            "xml" => "text/xml",
            "xpm" => "image/x-xpixmap",
            "xsl" => "text/x-xslt",
            "xslfo" => "text/x-xslfo",
            "xslt" => "text/x-xslt",
            "xwd" => "image/x-xwindowdump",
            "z" => "application/x-compress",
            "zabw" => "application/x-abiword",
            "zip" => "application/zip",
            "zoo" => "application/x-zoo"
        ];

        $ext = trim(strtolower($ext));

        if ($ext != '' && isset($mimetypes[$ext])) {
            return $mimetypes[$ext];
        } else {
            return "application/force-download";
        }
    }

    /**
     * @param string $str
     * @return string
     */
    static public function encodeString(string $str): string
    {
        $replace = [
            "А" => "A",
            "В" => "B",
            "Е" => "E",
            "К" => "K",
            "М" => "M",
            "Н" => "H",
            "О" => "O",
            "Р" => "P",
            "С" => "C",
            "Т" => "T",
            "Х" => "X",
            "х" => "x",
            "а" => "a",
            "е" => "e",
            "о" => "o",
            "с" => "c",
            "у" => "y"];

        $text = [];
        $quotes = [];
        $quotes[] = 'cyrillic';
        $quotes[] = 'latin';

        $array = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($array ?? [] as $char) {
            srand((double)microtime() * 1000000);
            $random_number = rand(0, count($quotes) - 1);

            if ($quotes[$random_number] == 'latin')
                $text[] = strtr($char, $replace);
            else
                $text[] = $char;
        }

        return implode("", $text);
    }

    /**
     * @param string $str
     * @return string
     */
    static public function removeHtmlTags(string $str): string
    {
        $str = strip_tags($str);
        return html_entity_decode($str);
    }

    /**
     * @param string $url
     * @return string
     */
    static public function getDomain(string $url): string
    {
        $parce = parse_url($url);
        return isset($parce['host']) ? $parce['host'] : $parce['path'];
    }

    /**
     * @param string $url
     * @return string
     */
    static public function getScheme(string $url): string
    {
        $parce = parse_url($url);
        return isset($parce['scheme']) ? $parce['scheme'] : 'http';
    }

    /**
     * @return string
     */
    public static function getUrl(): string
    {
        if (dirname($_SERVER['SCRIPT_NAME']) == '/' | dirname($_SERVER['SCRIPT_NAME']) == '\\')
            $dir = '/';
        else
            $dir = dirname($_SERVER['SCRIPT_NAME']) . '/';

        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $dir;
        $url = explode('?', $url);

        return $url[0] ?? '';
    }

    /**
     * @return array
     */
    static public function phpinfoArray(): array
    {
        ob_start();
        phpinfo();
        $info_arr = [];
        $info_lines = explode("\n", strip_tags(ob_get_clean(), "<tr><td><h2>"));
        $cat = "General";
        foreach ($info_lines ?? [] as $line) {
            // new cat?
            preg_match("~<h2>(.*)</h2>~", $line, $title) ? $cat = $title[1] : null;
            if (preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val)) {
                $info_arr[$cat][$val[1]] = $val[2];
            } elseif (preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val)) {
                $info_arr[$cat][$val[1]] = array("local" => $val[2], "master" => $val[3]);
            }
        }

        return $info_arr;
    }

    /**
     * @param object|array $el
     * @param bool $first
     * @return string
     */
    public static function tree(object|array $el, bool $first = true): string
    {
        if (is_object($el)) $el = (array)$el;

        if ($el) {
            if ($first) {
                $out = '<ul id="tree-checkbox" class="tree-checkbox treeview">';
            } else {
                $out = '<ul>';
            }

            foreach ($el as $k => $v) {
                if (is_object($v)) $v = (array)$v;
                if ($v) {
                    $out .= "<li><strong> " . $k . " :</strong> ";
                    if (is_array($v)) {
                        $out .= self::tree($v, false);
                    } else {
                        $out .= $v;
                    }
                    $out .= "</li>";
                }
            }
            $out .= "</ul>";

            return $out;
        } else {
            return '';
        }
    }

    /**
     * @param string|null $str
     * @return string|null
     */
    public static function charsetList(?string $str): ?string
    {
        $str = preg_replace("/^utf\-8$/i", trans('frontend.str.charutf8'), $str);
        $str = preg_replace("/^iso\-8859\-1$/i", trans('frontend.str.iso88591'), $str);
        $str = preg_replace("/^iso\-8859\-2$/i", trans('frontend.str.iso88592'), $str);
        $str = preg_replace("/^iso\-8859\-3$/i", trans('frontend.str.iso88593'), $str);
        $str = preg_replace("/^iso\-8859\-4$/i", trans('frontend.str.iso88594'), $str);
        $str = preg_replace("/^iso\-8859\-5$/i", trans('frontend.str.iso88595'), $str);
        $str = preg_replace("/^koi8\-r$/i", trans('frontend.str.koi8r'), $str);
        $str = preg_replace("/^koi8\-u$/i", trans('frontend.str.koi8u'), $str);
        $str = preg_replace("/^iso\-8859\-6$/i", trans('frontend.str.iso88596'), $str);
        $str = preg_replace("/^iso\-8859\-8$/i", trans('frontend.str.iso88598'), $str);
        $str = preg_replace("/^iso\-8859\-7$/i", trans('frontend.str.iso88597'), $str);
        $str = preg_replace("/^iso\-8859\-9$/i", trans('frontend.str.iso88599'), $str);
        $str = preg_replace("/^iso\-8859\-10$/i", trans('frontend.str.iso885910'), $str);
        $str = preg_replace("/^iso\-8859\-13$/i", trans('frontend.str.iso885913'), $str);
        $str = preg_replace("/^iso\-8859\-14$/i", trans('frontend.str.iso885914'), $str);
        $str = preg_replace("/^iso\-8859\-15$/i", trans('frontend.str.iso885915'), $str);
        $str = preg_replace("/^iso\-8859\-16$/i", trans('frontend.str.iso885916'), $str);
        $str = preg_replace("/^windows\-1250$/i", trans('frontend.str.windows1250'), $str);
        $str = preg_replace("/^windows\-1251$/i", trans('frontend.str.windows1251'), $str);
        $str = preg_replace("/^windows\-1252$/i", trans('frontend.str.windows1252'), $str);
        $str = preg_replace("/^windows\-1253$/i", trans('frontend.str.windows1253'), $str);
        $str = preg_replace("/^windows\-1254$/i", trans('frontend.str.windows1254'), $str);
        $str = preg_replace("/^windows\-1255$/i", trans('frontend.str.windows1255'), $str);
        $str = preg_replace("/^windows\-1256$/i", trans('frontend.str.windows1256'), $str);
        $str = preg_replace("/^windows\-1257$/i", trans('frontend.str.windows1257'), $str);
        $str = preg_replace("/^windows\-1258$/i", trans('frontend.str.windows1258'), $str);
        $str = preg_replace("/^gb2312$/i", trans('frontend.str.gb2312'), $str);
        $str = preg_replace("/^big5$/i", trans('frontend.str.big5'), $str);
        $str = preg_replace("/^iso-2022\-jp$/i", trans('frontend.str.iso2022jp'), $str);
        $str = preg_replace("/^ks_c_5601\-1987$/i", trans('frontend.str.ksc56011987'), $str);
        $str = preg_replace("/^euc\-kr$/i", trans('frontend.str.euckr'), $str);
        $str = preg_replace("/^windows\-874$/i", trans('frontend.str.windows874'), $str);

        return $str;
    }

    /**
     * @param string $envKey
     * @param string $envValue
     * @return void
     */
    public static function setEnvironmentValue(string $envKey, string $envValue): void
    {
        $path = app()->environmentFilePath();
        $escaped = preg_quote('="' . env($envKey) . '"', '/');

        file_put_contents($path, preg_replace(
            "/^{$envKey}{$escaped}/m",
            "{$envKey}=\"{$envValue}\"",
            file_get_contents($path)
        ));
    }

    /**
     * @param string $str
     * @return string
     */
    public static function macrosReplacement(string $str): string
    {
        foreach (Macros::get() ?? [] as $macros) {
            $str = str_replace("{{" . $macros->name . "}}", $macros->getValueByType(), $str);
        }

        return $str;
    }
}
