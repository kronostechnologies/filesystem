<?php

namespace Kronos\FileSystem\Mount;


use Kronos\FileSystem\Exception\WrongFileSystemTypeException;
use Kronos\FileSystem\File\File;
use League\Flysystem\Filesystem;

abstract class FlySystemBaseMount implements MountInterface
{

    /**
     * @var Filesystem
     */
    protected $mount;

    /**
     * @var PathGeneratorInterface
     */
    protected $pathGenerator;

    public function __construct(PathGeneratorInterface $pathGenerator, Filesystem $mount)
    {
        if (!$this->isFileSystemValid($mount)) {
            throw new WrongFileSystemTypeException($this->getMountType(), get_class($mount->getAdapter()));
        }
        $this->mount = $mount;
        $this->pathGenerator = $pathGenerator;
    }

    /**
     * @param Filesystem $mount
     * @return bool
     */
    abstract protected function isFileSystemValid(Filesystem $mount);

    /**
     * @param string $uuid
     * @param $fileName
     * @return File
     */
    public function get($uuid, $fileName)
    {
        $path = $this->pathGenerator->generatePath($uuid, $fileName);
        $flySystemFile = $this->mount->get($path);
        return new File($flySystemFile);
    }

    /**
     * Write a new file using a stream.
     *
     * @param string $uuid
     * @param string $filePath
     * @param $fileName
     * @return bool
     */
    public function put($uuid, $filePath, $fileName)
    {
        $path = $this->pathGenerator->generatePath($uuid, $fileName);
        return $this->mount->put($path, $this->getFileContent($filePath));
    }

    /**
     * @param $uuid
     * @param $stream
     * @param $fileName
     * @return mixed
     */
    public function putStream($uuid, $stream, $fileName)
    {
        $path = $this->pathGenerator->generatePath($uuid, $fileName);
        return $this->mount->putStream($path, $stream);
    }

    public function copy($sourceUuid, $targetUuid, $fileName)
    {
        $sourcePath = $this->pathGenerator->generatePath($sourceUuid, $fileName);
        $targetPath = $this->pathGenerator->generatePath($targetUuid, $fileName);

        return $this->mount->copy($sourcePath, $targetPath);
    }

    /**
     *
     * Delete a file.
     *
     * @param string $uuid
     * @param $fileName
     * @return bool
     */
    public function delete($uuid, $fileName)
    {
        $path = $this->pathGenerator->generatePath($uuid, $fileName);
        return $this->mount->delete($path);
    }

    /**
     * @param $uuid
     * @param $fileName
     * @return bool
     */
    public function has($uuid, $fileName)
    {
        $path = $this->pathGenerator->generatePath($uuid, $fileName);
        return $this->mount->has($path);
    }

    /**
     * @return mixed
     */
    public function getMountType()
    {
        return static::MOUNT_TYPE;
    }

    /**
     * @param string $uuid
     * @param $fileName
     * @return string
     */
    public function getPath($uuid, $fileName)
    {
        return $this->pathGenerator->generatePath($uuid, $fileName);
    }

    /**
     * @param string $path
     * @return string
     */
    protected function getFileContent($path)
    {
        return file_get_contents($path);
    }

    /**
     * Encode HTTP Content-Disposition header as stated in the RFC2616
     *
     * RFC 2616 defines the Content-Disposition response header field, but points out that it is not part of the HTTP/1.1 Standard.
     * This specification takes over the definition and registration of Content-Disposition, as used in HTTP, and clarifies internationalization aspects.
     *
     * @param string $filename
     * @param string $charset $filename charset.
     * @param bool $addAttachment
     * @return string Content-Disposition header value encoded using the RFC6266 rules.
     */
    protected function getRFC6266ContentDisposition($filename, $charset = 'UTF-8', $addAttachment = true)
    {
        //setlocale(LC_CTYPE, 'C.UTF-8', 'en_CA.utf8', 'en_US.utf8');

        $header = $addAttachment ? 'attachment;' : '';

        $asciiFilename = $this->getASCIIFileName($filename, $charset);

        $header .= 'filename=' . $this->quoteRFC2616HeaderValue($asciiFilename);

        if ($asciiFilename == $filename) {
            return $header;
        }

        if ($charset != 'UTF-8') {
            $utf8Filename = iconv($charset, 'UTF-8//IGNORE', $filename);
        } else {
            $utf8Filename = $filename;
        }

        //Include a "filename*" parameter where the desired filename cannot be expressed faithfully using the "filename" form.
        //Use UTF-8 as the encoding of the "filename*" parameter, when present, because at least one existing implementation only implements that encoding.
        $header .= ';' . self::encodeRFC5987("filename", $utf8Filename, 'UTF-8', 'fr');

        return $header;
    }

    /**
     * @param string $filename
     * @param string $charset
     * @return string
     */
    private function getASCIIFileName($filename, $charset){
        //Avoid using non-ASCII characters in the filename parameter.
        //Although most existing implementations will decode them as ISO‑8859‑1,
        //some will apply heuristics to detect UTF-8, and thus might fail on certain names.
        $asciiFilename = $this->toAscii($filename, $charset);

        //Avoid including the percent character followed by two hexadecimal characters (e.g., %A9)
        //in the filename parameter, since some existing implementations consider it to be an escape
        //character, while others will pass it through unchanged.
        $asciiFilename = preg_replace('/%[0-9a-fA-F]{2}/', '', $asciiFilename);

        //Avoid including the "\" character in the quoted-string form of the filename parameter, as
        //escaping is not implemented by some user agents, and "\" can be considered an illegal path character.
        $asciiFilename = preg_replace('/\\\\/', '', $asciiFilename);

        //IE Truncate file name containing semicolons
        $asciiFilename = preg_replace('/;/', '_', $asciiFilename);

        return $asciiFilename;
    }

    /**
     * Convert $string to ASCII.  This function deprecate replaceFrenchAccents.
     * @param string $string
     * @param string $in_charset UTF-8|ISO-8859-1|charset
     * @return string
     */
    private function toAscii($string, $in_charset = 'UTF-8')
    {
        $ascii_string = iconv($in_charset, 'ASCII//TRANSLIT//IGNORE', $string);
        if (!$ascii_string) {
            return $string;
        }
        return $ascii_string;
    }

    /**
     * Encode header ASCII value using the basic HTTP/1.1 specification using the token or the quoted-string method.
     * @param string $value
     * @return string Quoted value (if necessary)
     */
    private function quoteRFC2616HeaderValue($value)
    {

        if (!preg_match('/[\x00-\x20*%\'()<>@,;:\\\\"\/[\]?={}\x80-\xFF\s\t]/', $value)) {
            //token
            return $value;
        } else {
            //quoted-string
            //any OCTET except CTLs, but including LWS
            $value = preg_replace('/[\x00-\x1F\x80]/', '', $value);
            $value = str_replace('"', '\"', $value);

            return '"' . $value . '"';
        }
    }

    /**
     * Encode http header field as specified in the RFC5987 (a superset of RFC2231)
     * (Character Set and Language Encoding for Hypertext Transfer Protocol (HTTP) Header Field Parameters)
     * @param string $name
     * @param string $value
     * @param string $charset
     * @param string $lang
     * @return string
     * @example filename*=UTF-8'fr'Test%20%C3%A7%20accents%20%C3%A4%20encore%20%C3%AB%20encore%20%20%C3%AF%20encore%20%20%5D.doc
     */
    private function encodeRFC5987($name, $value, $charset, $lang)
    {
        //Parameter Continuations aren't needed
        return $this->encodeRFC2231($name, $value, $charset, $lang, 9999);
    }

    /**
     * Encode http header field as specified in the RFC2231
     *
     * (MIME Parameter Value and Encoded Word Extensions: Character Sets, Languages, and Continuations)
     *
     * @param string $name Field name
     * @param string $value Field value
     * @param string $charset Field charset
     * @param string $lang Language
     * @param integer $ll Line Length (for line Continuations)
     * @return string Encoded field
     * @example filename*0*=UTF-8'fr'Encore%20%C3%A8%20des%20%C3%A0%20accents%20%C3%B9%20plei;
     *            filename*1*=n%20%C3%88%20plein%20%20%C3%80%20plein%20%20%C3%99%20encore%20{.d;
     *            filename*2*=oc
     */
    private function encodeRFC2231($name, $value, $charset = 'UTF-8', $lang = 'fr', $ll = 78)
    {
        if (strlen($name) === 0 || preg_match('/[\x00-\x20*\'%()<>@,;:\\\\"\/[\]?=\x80-\xFF]/', $name)) {
            // invalid parameter name;
            return false;
        }
        if (strlen($charset) !== 0 && !preg_match('/^[A-Za-z]{1,8}(?:-[A-Za-z0-9]{1,8})*$/', $charset)) {
            // invalid charset;
            return false;
        }
        if (strlen($lang) !== 0 && !preg_match('/^[A-Za-z]{1,8}(?:-[A-Za-z]{1,8})*$/', $lang)) {
            // invalid language;
            return false;
        }
        $value = "$charset'$lang'" . preg_replace_callback('/[\x00-\x20*\'%()<>@,;:\\\\"\/[\]?=\x80-\xFF]/',
                function ($match) {
                    return rawurlencode($match[0]);
                }, $value);
        $nlen = strlen($name);
        $vlen = strlen($value);
        if (strlen($name) + $vlen > $ll - 3) {
            $sections = array();
            $section = 0;
            for ($i = 0, $j = 0; $i < $vlen; $i += $j) {
                $j = $ll - $nlen - strlen($section) - 4;
                $sections[$section++] = substr($value, $i, $j);
            }
            for ($i = 0, $n = $section; $i < $n; $i++) {
                $sections[$i] = " $name*$i*=" . $sections[$i];
            }
            return implode(";\r\n", $sections);
        } else {
            return "$name*=$value";
        }
    }
}