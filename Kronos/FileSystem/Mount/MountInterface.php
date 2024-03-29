<?php

namespace Kronos\FileSystem\Mount;

use GuzzleHttp\Promise\PromiseInterface;
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

    public function copyAsync($sourceUuid, $targetUuid, $fileName): PromiseInterface;

    /**
     * Delete a file.
     *
     * @param string $uuid
     * @param $fileName
     */
    public function delete($uuid, $fileName);

    public function deleteAsync($uuid, $filename): PromiseInterface;

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

    public function putAsync($uuid, $filePath, $fileName): PromiseInterface;

    /**
     * @param $uuid
     * @param $stream
     * @param $fileName
     * @return mixed
     */
    public function putStream($uuid, $stream, $fileName);

    public function putStreamAsync($uuid, $stream, $fileName): PromiseInterface;

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

    public function hasAsync($uuid, $fileName): PromiseInterface;

    /**
     * @param string $uuid
     * @param $fileName
     * @return mixed
     */
    public function getPath($uuid, $fileName);

    /**
     * @param string $uuid
     * @param $fileName
     * @return Metadata|false
     */
    public function getMetadata($uuid, $fileName);

    /**
     * @return string
     */
    public function getMountType();

    /**
     * @return bool
     */
    public function isSelfContained();

    public function useDirectDownload(): bool;
}
