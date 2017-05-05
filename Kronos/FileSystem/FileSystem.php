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

class FileSystem implements FileSystemInterface {

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

	public function __construct(Selector $mountSelector, FileRepositoryInterface $fileRepository, MetadataTranslator $metadataTranslator = null) {
		$this->mountSelector = $mountSelector;
		$this->fileRepository = $fileRepository;
		$this->metadataTranslator = ($metadataTranslator ?: new MetadataTranslator());

	}

	/**
	 * @param string $filePath
	 * @param string $fileName
	 * @return int
	 * @throws FileCantBeWrittenException
	 */
	public function put($filePath,$fileName){
		$mount = $this->mountSelector->getImportationMount();
		$fileUuid = $this->fileRepository->addNewFile($mount->getMountType(),$fileName);

		if(!$mount->put($fileUuid,$filePath)){
			$this->fileRepository->delete($fileUuid);
			throw new FileCantBeWrittenException($mount->getMountType());
		}

		return $fileUuid;
	}

	/**
	 * @param int $id
	 * @return File
	 */
	public function get($id){
		$mount = $this->getMountForId($id);

		$file = $mount->get($id);
		$file->metadata = $this->getMetadata($id);

		return $file;
	}

	/**
	 * @param int $id
	 * @return string
	 * @throws MountNotFoundException
	 */
	public function getDownloadableLink($id){
		$mount = $this->getMountForId($id);
		$signedUrl = $mount->getSignedUrl($id);
		return $signedUrl;
	}


	/**
	 * @param int $id
	 * @return Metadata
	 * @throws MountNotFoundException
	 */
	public function getMetadata($id){
		$mount = $this->getMountForId($id);
		$fileName = $this->fileRepository->getFileName($id);

		$metadata = $mount->getMetadata($id);
		$metadata->name = $fileName;

		return $this->metadataTranslator->translateInternalToExposed($metadata);
	}

	/**
	 * @param int $id
	 * @throws FileNotFoundException
	 */
	public function delete($id){
		$mount = $this->getMountForId($id);
		$mount->delete($id);
	}

	/**
	 * @param int $id
	 * @throws MountNotFoundException
	 */
	public function retrieve($id) {
		$mount = $this->getMountForId($id);
		$mount->retrieve($id);
	}

	/**
	 * @param $id
	 * @return MountInterface
	 * @throws FileNotFoundException
	 */
	private function getMountForId($id){
		$mountType = $this->fileRepository->getFileMountType($id);
		$mount = $this->mountSelector->selectMount($mountType);

		if(is_null($mount)){
			throw new MountNotFoundException($mountType);
		}

		return $mount;
	}
}