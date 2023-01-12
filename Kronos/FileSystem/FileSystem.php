<?php

namespace Kronos\FileSystem;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Kronos\FileSystem\Copy\DestinationChooserFactory;
use Kronos\FileSystem\Copy\Factory as CopyFactory;
use Kronos\FileSystem\Exception\CantRetreiveFileException;
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
     * @var DestinationChooserFactory
     */
    private $destinationChooserFactory;

    /**
     * @var ExtensionList
     */
    protected $forceDownloadList;

    public function __construct(
        Selector $mountSelector,
        FileRepositoryInterface $fileRepository,
        MetadataTranslator $metadataTranslator = null,
        PromiseFactory $promiseFactory = null,
        CopyFactory $copyFactory = null
    ) {
        $this->mountSelector = $mountSelector;
        $this->fileRepository = $fileRepository;
        $this->metadataTranslator = $metadataTranslator ?? new MetadataTranslator();
        $this->promiseFactory = $promiseFactory ?? new PromiseFactory();

        $safeCopyFactory = $copyFactory ?? new CopyFactory();
        $this->destinationChooserFactory = $safeCopyFactory->createDestinationChooserFactory(
            $fileRepository,
            $mountSelector
        );
    }

    /**
     * @param string $filePath
     * @param string $fileName
     * @return string
     * @throws FileCantBeWrittenException
     * @throws MountNotFoundException
     */
    public function put($filePath, $fileName)
    {
        $mount = $this->mountSelector->getImportationMount();

        if ($mount === null) {
            throw new MountNotFoundException($this->mountSelector->getImportationMountType());
        }

        $fileUuid = $this->fileRepository->addNewFile($mount->getMountType(), $fileName);

        if (!$mount->put($fileUuid, $filePath, $fileName)) {
            $this->fileRepository->delete($fileUuid);
            throw new FileCantBeWrittenException($mount->getMountType());
        }

        return $fileUuid;
    }

    /**
     * @param string $filePath
     * @param string $fileName
     * @return PromiseInterface
     * @throws MountNotFoundException
     */
    public function putAsync($filePath, $fileName): PromiseInterface
    {
        $mount = $this->mountSelector->getImportationMount();

        if ($mount === null) {
            throw new MountNotFoundException($this->mountSelector->getImportationMountType());
        }

        $fileUuid = $this->fileRepository->addNewFile($mount->getMountType(), $fileName);

        $promise = $mount->putAsync($fileUuid, $filePath, $fileName);

        return $promise->then(
            static function () use ($fileUuid) {
                return $fileUuid;
            },
            function ($reason) use ($fileUuid, $mount) {
                $this->fileRepository->delete($fileUuid);
                throw new FileCantBeWrittenException(
                    $mount->getMountType(),
                    $reason instanceof Throwable ? $reason : null
                );
            }
        );
    }

    /**
     * @param resource $stream
     * @param string $fileName
     * @return string
     * @throws FileCantBeWrittenException
     * @throws MountNotFoundException
     */
    public function putStream($stream, $fileName)
    {
        $mount = $this->mountSelector->getImportationMount();

        if ($mount === null) {
            throw new MountNotFoundException($this->mountSelector->getImportationMountType());
        }

        $fileUuid = $this->fileRepository->addNewFile($mount->getMountType(), $fileName);

        if (!$mount->putStream($fileUuid, $stream, $fileName)) {
            $this->fileRepository->delete($fileUuid);
            throw new FileCantBeWrittenException($mount->getMountType());
        }

        return $fileUuid;
    }

    /**
     * @param resource $stream
     * @param string $fileName
     * @return PromiseInterface
     * @throws MountNotFoundException
     */
    public function putStreamAsync($stream, $fileName): PromiseInterface
    {
        $mount = $this->mountSelector->getImportationMount();

        if ($mount === null) {
            throw new MountNotFoundException($this->mountSelector->getImportationMountType());
        }

        $fileUuid = $this->fileRepository->addNewFile($mount->getMountType(), $fileName);

        $promise = $mount->putStreamAsync($fileUuid, $stream, $fileName);

        return $promise->then(
            static function () use ($fileUuid) {
                return $fileUuid;
            },
            function ($reason) use ($fileUuid, $mount) {
                $this->fileRepository->delete($fileUuid);
                throw new FileCantBeWrittenException(
                    $mount->getMountType(),
                    $reason instanceof Throwable ? $reason : null
                );
            }
        );
    }

    /**
     * @param string $id
     * @return File
     * @throws MountNotFoundException
     */
    public function get($id)
    {
        $mount = $this->getMountForId($id);
        $fileName = $this->fileRepository->getFileName($id);

        return $mount->get($id, $fileName);
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
     * @throws FileNotFoundException
     */
    public function getMetadata($id)
    {
        $mount = $this->getMountForId($id);
        $fileName = $this->fileRepository->getFileName($id);

        $metadata = $mount->getMetadata($id, $fileName);

        if ($metadata === false) {
            throw new FileNotFoundException($id);
        }

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

    public function copyAsync($id): PromiseInterface
    {
        $chooser = $this->destinationChooserFactory->getChooserForFileId($id);

        $fileName = $this->fileRepository->getFileName($id);
        $destinationId = $this->fileRepository->addNewFile($chooser->getDestinationMountType(), $fileName);

        if ($chooser->canUseCopy()) {
            $promise = $chooser->getDestinationMount()->copyAsync($id, $destinationId, $fileName);
        } else {
            $file = $chooser->getSourceMount()->get($id, $fileName);
            $promise = $chooser->getDestinationMount()->putStreamAsync($destinationId, $file->readStream(), $fileName);
        }

        return $promise->then(function($didCopyOrPut) use ($destinationId) {
           return $destinationId;
        });
    }

    /**
     * @param string $id
     * @throws FileNotFoundException
     * @throws MountNotFoundException
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

            return $mount
                ->hasAsync($id, $fileName)
                ->then(static function ($hasFile) use ($mount, $id, $fileName) {
                    if ($hasFile) {
                        return $mount->deleteAsync($id, $fileName);
                    }
                    return true; // Act as if file was deleted, it was not in mount anyway
                })
                ->then(function ($didDelete) use ($id) {
                    if ($didDelete) {
                        $this->fileRepository->delete($id);
                    }
                    return $didDelete;
                });
        } catch (Throwable $throwable) {
            return $this->promiseFactory->createRejectedPromise($throwable);
        }
    }

    /**
     * @param string $id
     * @throws MountNotFoundException
     * @throws CantRetreiveFileException
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
     * @throws MountNotFoundException
     */
    public function has($id)
    {
        $mount = $this->getMountForId($id);
        $fileName = $this->fileRepository->getFileName($id);
        return $mount->has($id, $fileName);
    }

    /**
     * @param string $id
     * @return PromiseInterface
     * @throws MountNotFoundException
     */
    public function hasAsync($id): PromiseInterface
    {
        $mount = $this->getMountForId($id);
        $fileName = $this->fileRepository->getFileName($id);
        return $mount->hasAsync($id, $fileName);
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
    private function getMountForId($id): MountInterface
    {
        $mountType = $this->fileRepository->getFileMountType($id);
        $mount = $this->mountSelector->selectMount($mountType);

        if (is_null($mount)) {
            throw new MountNotFoundException($mountType);
        }

        return $mount;
    }
}
