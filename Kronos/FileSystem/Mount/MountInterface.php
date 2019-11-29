<?php

namespace Kronos\FileSystem\Mount;

use Kronos\FileSystem\Exception\CantRetreiveFileException;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Internal\Metadata;

/**
 *
 * Interface MountInterface
 * @package Kronos\FileSystem\Mount
 */
interface MountInterface
{

    /**
     * @param string $uuid
     * @param $fileName
     * @return File
     */
    public function get($uuid, $fileName);

    /**
     * @param $uuid
     * @param $fileName
     * @param bool $forceDownload
     * @return mixed
     */
    public function getUrl($uuid, $fileName, $forceDownload = false);

    /**
     * @param $sourceUuid
     * @param $targetUuid
     * @param $fileName
     * @return mixed
     */
    public function copy($sourceUuid, $targetUuid, $fileName);

    /**
     * Delete a file.
     *
     * @param string $uuid
     * @param $fileName
     */
    public function delete($uuid, $fileName);


    /**
     * Write a new file using a stream.
     *
     * @param $uuid
     * @param $filePath
     * @param $fileName
     * @return mixed
     *
     */
    public function put($uuid, $filePath, $fileName);

    /**
     * @param $uuid
     * @param $stream
     * @param $fileName
     * @return mixed
     */
    public function putStream($uuid, $stream, $fileName);

    /**
     * @param string $uuid
     * @param $fileName
     * @throws CantRetreiveFileException
     */
    public function retrieve($uuid, $fileName);

    /**
     * @param $uuid
     * @param $fileName
     * @return bool
     */
    public function has($uuid, $fileName);

    /**
     * @param string $uuid
     * @param $fileName
     * @return mixed
     */
    public function getPath($uuid, $fileName);

    /**
     * @param string $uuid
     * @param $fileName
     * @return Metadata
     */
    public function getMetadata($uuid, $fileName);

    /**
     * @return string
     */
    public function getMountType();
}
