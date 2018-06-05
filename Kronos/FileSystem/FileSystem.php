<?php

namespace Kronos\FileSystem;

use Kronos\FileSystem\Exception\FileCantBeWrittenException;
use Kronos\FileSystem\Exception\FileNotFoundException;
use Kronos\FileSystem\Exception\MountNotFoundException;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Metadata;
use Kronos\FileSystem\File\Translator\MetadataTranslator;
use Kronos\FileSystem\Mount\MountInterface;
use Kronos\FileSystem\Mount\Selector;

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

    public function __construct(
        Selector $mountSelector,
        FileRepositoryInterface $fileRepository,
        MetadataTranslator $metadataTranslator = null
    ) {
        $this->mountSelector = $mountSelector;
        $this->fileRepository = $fileRepository;
        $this->metadataTranslator = ($metadataTranslator ?: new MetadataTranslator());

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
     * @param string $id
     * @return File
     */
    public function get($id)
    {
        $mount = $this->getMountForId($id);
        $fileName = $this->fileRepository->getFileName($id);

        $file = $mount->get($id, $fileName);
        $file->metadata = $this->getMetadata($id);

        return $file;
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
        $signedUrl = $mount->getUrl($id, $fileName);
        return $signedUrl;
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
     */
    public function copy($id)
    {
        $sourceMountType = $this->fileRepository->getFileMountType($id);
        $fileName = $this->fileRepository->getFileName($id);
        $destinationMountType = $this->mountSelector->getImportationMountType();
        $destinationMount = $this->mountSelector->selectMount($destinationMountType);
        $destinationId = $this->fileRepository->addNewFile($destinationMountType, $fileName);

        if ($sourceMountType == $destinationMountType) {
            $destinationMount->copy($id, $destinationId, $fileName);
        } else {
            $sourceMount = $this->mountSelector->selectMount($sourceMountType);
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