<?php

namespace Kronos\FileSystem;

use Kronos\FileSystem\Exception\FileCantBeWrittenException;
use Kronos\FileSystem\Exception\FileNotFoundException;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Metadata;
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

	public function __construct(Selector $mountSelector, FileRepositoryInterface $fileRepository) {
		$this->mountSelector = $mountSelector;
		$this->fileRepository = $fileRepository;
	}

	/**
	 * @param resource $file
	 * @return int
	 * @throws FileCantBeWrittenException
	 */
	public function put($file){
		$mount = $this->mountSelector->getImportationMount();
		$fileUuid = $this->fileRepository->addNewFile($mount->getMountType());

		if(!$mount->write($fileUuid,$file)){
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

		$file = new File();
		$file->resource = $mount->getResource($id);
		$file->metadata = $mount->getMetadata($id);

		return $file;
	}

	/**
	 * @param int $id
	 * @return string
	 * @throws FileNotFoundException
	 */
	public function getDownloadableLink($id){
		$mount = $this->getMountForId($id);
		$signedUrl = $mount->getSignedUrl($id);
		return $signedUrl;
	}


	/**
	 * @param int $id
	 * @return Metadata
	 * @throws FileNotFoundException
	 */
	public function getMetadata($id){
		$mount = $this->getMountForId($id);
		$metadata = $mount->getMetadata($id);

		return $metadata;
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
	 * @throws FileNotFoundException
	 */
	public function retrieve($id) {
		//Pas convaincu de quoi faire ici ?
		//Surement pas une recherche de mount normal.
		//On assume S3 et si on ne trouve pas fuck it?
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
			throw new FileNotFoundException($id);
		}

		return $mount;
	}
}