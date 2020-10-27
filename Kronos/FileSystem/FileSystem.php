<?php

namespace Kronos\FileSystem;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Kronos\FileSystem\Exception\FileCantBeWrittenException;
use Kronos\FileSystem\Exception\FileNotFoundException;
use Kronos\FileSystem\Exception\MountNotFoundException;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Metadata;
use Kronos\FileSystem\File\Translator\MetadataTranslator;
use Kronos\FileSystem\Mount\MountInterface;
use Kronos\FileSystem\Mount\Selector;
use Throwable;

class FileSystem implements FileSystemInterface
{

    /**
     * @var Selector
     */
    private $mountSelector;

    /**
     * @var FileRepositoryInterface
     */
    private $fileRepository;

    /**
     * @var MetadataTranslator
     */
    private $metadataTranslator;

    /**
     * @var PromiseFactory
     */
    private $promiseFactory;

    /**
     * @var ExtensionList
     */
    protected $forceDownloadList;

    public function __construct(
        Selector $mountSelector,
        FileRepositoryInterface $fileRepository,
        MetadataTranslator $metadataTranslator = null,
        PromiseFactory $factory = null
    ) {
        $this->mountSelector = $mountSelector;
        $this->fileRepository = $fileRepository;
        $this->metadataTranslator = ($metadataTranslator ?: new MetadataTranslator());
        $this->promiseFactory = $factory ?? new PromiseFactory();
    }

    /**
     * @param string $filePath
     * @param string $fileName
     * @return string
     * @throws FileCantBeWrittenException
     */
    public function put($filePath, $fileName)
    {
        $mount = $this->mountSelector->getImportationMount();
        $fileUuid = $this->fileRepository->addNewFile($mount->getMountType(), $fileName);

        if (!$mount->put($fileUuid, $filePath, $fileName)) {
            $this->fileRepository->delete($fileUuid);
            throw new FileCantBeWrittenException($mount->getMountType());
        }

        return $fileUuid;
    }

    /**
     * @param resource $stream
     * @param string $fileName
     * @return string
     * @throws FileCantBeWrittenException
     */
    public function putStream($stream, $fileName)
    {
        $mount = $this->mountSelector->getImportationMount();
        $fileUuid = $this->fileRepository->addNewFile($mount->getMountType(), $fileName);


        if (!$mount->putStream($fileUuid, $stream, $fileName)) {
            $this->fileRepository->delete($fileUuid);
            throw new FileCantBeWrittenException($mount->getMountType());
        }

        return $fileUuid;
    }

    /**
     * @param string $id
     * @return File
     */
    public function get($id)
    {
        $mount = $this->getMountForId($id);
        $fileName = $this->fileRepository->getFileName($id);

        return  $mount->get($id, $fileName);
    }

    /**
     * @param string $id
     * @return string
     * @throws MountNotFoundException
     */
    public function getUrl($id)
    {
        $mount = $this->getMountForId($id);
        $fileName = $this->fileRepository->getFileName($id);
        return $mount->getUrl($id, $fileName, $this->shouldForceDownload($fileName));;
    }

    /**
     * @param ExtensionList $extensionList
     */
    public function setForceDownloadExtensionList(ExtensionList $extensionList)
    {
        $this->forceDownloadList = $extensionList;
    }

    protected function shouldForceDownload($fileName)
    {
        return $this->forceDownloadList ? $this->forceDownloadList->isInList($fileName) : false;
    }

    /**
     * @param string $id
     * @return Metadata
     * @throws MountNotFoundException
     */
    public function getMetadata($id)
    {
        $mount = $this->getMountForId($id);
        $fileName = $this->fileRepository->getFileName($id);

        $metadata = $mount->getMetadata($id, $fileName);
        $metadata->name = $fileName;

        return $this->metadataTranslator->translateInternalToExposed($metadata);
    }

    /**
     * @param string $id
     * @return string
     * @throws MountNotFoundException
     */
    public function copy($id)
    {
        $sourceMountType = $this->fileRepository->getFileMountType($id);
        $sourceMount = $this->mountSelector->selectMount($sourceMountType);
        $fileName = $this->fileRepository->getFileName($id);

        if ($sourceMount->isSelfContained()) {
            $destinationMountType = $sourceMountType;
            $destinationMount = $sourceMount;
        } else {
            $destinationMountType = $this->mountSelector->getImportationMountType();
            $destinationMount = $this->mountSelector->selectMount($destinationMountType);
        }
        $destinationId = $this->fileRepository->addNewFile($destinationMountType, $fileName);

        if ($sourceMountType == $destinationMountType) {
            $destinationMount->copy($id, $destinationId, $fileName);
        } else {
            $file = $sourceMount->get($id, $fileName);
            $destinationMount->putStream($destinationId, $file->readStream(), $fileName);
        }

        return $destinationId;
    }


    /**
     * @param string $id
     * @throws FileNotFoundException
     */
    public function delete($id)
    {
        $fileName = $this->fileRepository->getFileName($id);
        $mount = $this->getMountForId($id);

        if (!$mount->has($id, $fileName) || $mount->delete($id, $fileName)) {
            $this->fileRepository->delete($id);
        }
    }

    public function deleteAsync($id): PromiseInterface
    {
        try {
            $fileName = $this->fileRepository->getFileName($id);
            $mount = $this->getMountForId($id);

            if ($mount->has($id, $fileName)) {
                $promise = $mount->deleteAsync($id, $fileName);
            } else {
                $promise = $this->promiseFactory->createFulfilledPromise(true);
            }

            return $promise->then(function ($didDelete) use ($id) {
                if ($didDelete) {
                    $this->fileRepository->delete($id);
                }
                return $didDelete;
            });
        } catch(Throwable $throwable) {
            return $this->promiseFactory->createRejectedPromise($throwable);
        }
    }

    /**
     * @param string $id
     * @throws MountNotFoundException
     */
    public function retrieve($id)
    {
        $mount = $this->getMountForId($id);
        $fileName = $this->fileRepository->getFileName($id);
        $mount->retrieve($id, $fileName);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        $mount = $this->getMountForId($id);
        $fileName = $this->fileRepository->getFileName($id);
        return $mount->has($id, $fileName);
    }

    /**
     * @param string $id
     * @return bool
     * @throws MountNotFoundException
     */
    public function useDirectDownload(string $id): bool
    {
        return $this->getMountForId($id)->useDirectDownload();
    }

    /**
     * @param string $id
     * @return MountInterface
     * @throws MountNotFoundException
     */
    private function getMountForId($id)
    {
        $mountType = $this->fileRepository->getFileMountType($id);
        $mount = $this->mountSelector->selectMount($mountType);

        if (is_null($mount)) {
            throw new MountNotFoundException($mountType);
        }

        return $mount;
    }
}
