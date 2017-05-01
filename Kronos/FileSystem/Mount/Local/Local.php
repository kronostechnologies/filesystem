<?php

namespace Kronos\FileSystem\Mount\Local;

use DateTime;
use Kronos\FileSystem\Exception\CantRetreiveFileException;
use Kronos\FileSystem\Exception\WrongFileSystemTypeException;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Metadata;
use Kronos\FileSystem\Mount\MountInterface;
use Kronos\FileSystem\Mount\PathGeneratorInterface;
use League\Flysystem\Adapter\Local as LocalFlySystem;
use League\Flysystem\Filesystem;

class Local implements MountInterface {

	const MOUNT_TYPE = 'LOCAL';
	const SIGNED_URL_BASE_PATH = 'utils/get_document.php?id=';

	/**
	 * @var Filesystem
	 */
	private $mount;

	/**
	 * @var PathGeneratorInterface
	 */
	private $pathGenerator;

	public function __construct(PathGeneratorInterface $pathGenerator,Filesystem $mount) {
		if(!$this->isLocalMount($mount)){
			throw new WrongFileSystemTypeException($this->getMountType(),get_class($mount->getAdapter()));
		}
		$this->mount = $mount;
		$this->pathGenerator = $pathGenerator;
	}

	/**
	 * @param Filesystem $mount
	 * @return bool
	 */
	private function isLocalMount(Filesystem $mount){
		return $mount->getAdapter() instanceof  LocalFlySystem;
	}


	/**
	 * @param string $uuid
	 * @return File
	 */
	public function get($uuid) {
		$path = $this->pathGenerator->generatePath($uuid);
		$flySystemFile = $this->mount->get($path);
		return new File($flySystemFile);
	}

	/**
	 * @param string $uuid
	 * @return string
	 */
	public function getSignedUrl($uuid) {
		return self::SIGNED_URL_BASE_PATH.$uuid;
	}

	/**
	 * Delete a file.
	 *
	 * @param string $uuid
	 */
	public function delete($uuid) {
		$path = $this->pathGenerator->generatePath($uuid);
		return $this->mount->delete($path);
	}

	/**
	 * Write a new file using a stream.
	 *
	 * @param string $uuid
	 * @param string $filePath
	 * @return bool
	 */
	public function put($uuid, $filePath) {
		$path = $this->pathGenerator->generatePath($uuid);
		return $this->mount->put($path,$this->getFileContent($filePath));
	}

	/**
	 * @param string $uuid
	 * @throws CantRetreiveFileException
	 */
	public function retrieve($uuid) {
		throw new CantRetreiveFileException($uuid);
	}

	/**
	 * @param string $uuid
	 * @return Metadata|false
	 */
	public function getMetadata($uuid) {
		$path = $this->pathGenerator->generatePath($uuid);

		if($localMetadata = $this->mount->getMetadata($path)){
			$metadata = new Metadata();

			$metadata->size = isset($localMetadata['size']) ?$localMetadata['size'] : 0 ;
			$metadata->lastModifiedDate = new DateTime('@' .$localMetadata['timestamp']);
			$metadata->mimetype = $this->mount->getMimetype($path);

			return $metadata;
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function getMountType() {
		return self::MOUNT_TYPE;
	}


	/**
	 * @param string $path
	 * @return string
	 */
	protected function getFileContent($path){
		return file_get_contents($path);
	}
}