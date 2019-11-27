<?php

namespace Kronos\FileSystem\Mount;

use Kronos\FileSystem\Exception\CantRetreiveFileException;
use Kronos\FileSystem\Exception\InvalidOperationException;
use Kronos\FileSystem\File\File;

abstract class ReferenceMount implements MountInterface
{
    const MOUNT_TYPE = 'REFERENCE';

    /**
     * @var GetterInterface
     */
    private $getter;

    /**
     * ReferenceMount constructor.
     * @param GetterInterface $getter
     */
    public function __construct(GetterInterface $getter)
    {
        $this->getter = $getter;
    }

    /**
     * @param string $uuid
     * @param $fileName
     * @return File
     */
    public function get($uuid, $fileName)
    {
        return $this->getter->get($uuid, $fileName);
    }

    /**
     * @param $sourceUuid
     * @param $targetUuid
     * @param $fileName
     * @return mixed
     */
    public function copy($sourceUuid, $targetUuid, $fileName)
    {
        return true;
    }

    /**
     * Delete a file.
     *
     * @param string $uuid
     * @param $fileName
     */
    public function delete($uuid, $fileName)
    {
    }

    /**
     * Write a new file using a stream.
     *
     * @param $uuid
     * @param $filePath
     * @param $fileName
     * @return mixed
     *
     */
    public function put($uuid, $filePath, $fileName)
    {
        return true;
    }

    /**
     * @param $uuid
     * @param $stream
     * @param $fileName
     * @return mixed
     * @throws InvalidOperationException
     */
    public function putStream($uuid, $stream, $fileName)
    {
        throw new InvalidOperationException(static::MOUNT_TYPE);
    }

    /**
     * @param string $uuid
     * @param $fileName
     * @throws CantRetreiveFileException
     */
    public function retrieve($uuid, $fileName)
    {
        throw new CantRetreiveFileException(static::MOUNT_TYPE);
    }

    /**
     * @param $uuid
     * @param $fileName
     * @return bool
     */
    public function has($uuid, $fileName)
    {
        return true;
    }

    /**
     * @param string $uuid
     * @param $fileName
     * @return mixed
     * @throws InvalidOperationException
     */
    public function getPath($uuid, $fileName)
    {
        throw new InvalidOperationException(static::MOUNT_TYPE);
    }

    /**
     * @return bool
     */
    public function isSelfContained()
    {
        return true;
    }
}
