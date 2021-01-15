<?php

namespace Kronos\FileSystem\Mount;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Kronos\FileSystem\Exception\CantRetreiveFileException;
use Kronos\FileSystem\Exception\InvalidOperationException;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\PromiseFactory;

abstract class ReferenceMount implements MountInterface
{
    const MOUNT_TYPE = 'REFERENCE';

    /**
     * @var GetterInterface
     */
    protected $getter;

    /**
     * @var PromiseFactory
     */
    protected $promiseFactory;

    /**
     * @var bool
     */
    private $useDirectDownload = false;

    /**
     * ReferenceMount constructor.
     * @param GetterInterface $getter
     */
    public function __construct(GetterInterface $getter, PromiseFactory $factory = null)
    {
        $this->getter = $getter;
        $this->promiseFactory = $factory ?? new PromiseFactory();
    }

    /**
     * @param bool $useDirectDownload
     */
    public function setUseDirectDownload(bool $useDirectDownload): void
    {
        $this->useDirectDownload = $useDirectDownload;
    }

    public function useDirectDownload(): bool
    {
        return $this->useDirectDownload;
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

    public function copyAsync($sourceUuid, $targetUuid, $fileName): PromiseInterface {
        return $this->promiseFactory->createRejectedPromise(new InvalidOperationException(static::MOUNT_TYPE));
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

    public function deleteAsync(
        $uuid,
        $filename
    ): PromiseInterface {
        return $this->promiseFactory->createRejectedPromise(new InvalidOperationException(static::MOUNT_TYPE));
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
     * Write a new file using a stream.
     *
     * @param $uuid
     * @param $filePath
     * @param $fileName
     * @return PromiseInterface
     *
     */
    public function putAsync($uuid, $filePath, $fileName): PromiseInterface
    {
        return $this->promiseFactory->createFulfilledPromise(true);
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
     * @param $uuid
     * @param $stream
     * @param $fileName
     * @return PromiseInterface
     */
    public function putStreamAsync($uuid, $stream, $fileName): PromiseInterface
    {
        return $this->promiseFactory->createRejectedPromise(new InvalidOperationException(static::MOUNT_TYPE));
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
