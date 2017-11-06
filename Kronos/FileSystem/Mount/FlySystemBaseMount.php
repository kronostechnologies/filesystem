<?php
/**
 * Created by PhpStorm.
 * User: mdemers
 * Date: 2017-05-01
 * Time: 9:31 AM
 */

namespace Kronos\FileSystem\Mount;


use Kronos\FileSystem\Exception\WrongFileSystemTypeException;
use Kronos\FileSystem\File\File;
use League\Flysystem\Filesystem;

abstract class FlySystemBaseMount implements MountInterface{

	/**
	 * @var Filesystem
	 */
	protected $mount;
	/**
	 * @var PathGeneratorInterface
	 */
	protected $pathGenerator;

	public function __construct(PathGeneratorInterface $pathGenerator,Filesystem $mount) {
		if(!$this->isFileSystemValid($mount)){
			throw new WrongFileSystemTypeException($this->getMountType(),get_class($mount->getAdapter()));
		}
		$this->mount = $mount;
		$this->pathGenerator = $pathGenerator;
	}

	/**
	 * @param Filesystem $mount
	 * @return bool
	 */
	abstract protected function isFileSystemValid(Filesystem $mount);

	/**
	 * @param string $uuid
	 * @param $fileName
	 * @return File
	 */
	public function get($uuid, $fileName) {
		$path = $this->pathGenerator->generatePath($uuid, $fileName);
		$flySystemFile = $this->mount->get($path);
		return new File($flySystemFile);
	}

	/**
	 * Write a new file using a stream.
	 *
	 * @param string $uuid
	 * @param string $filePath
	 * @param $fileName
	 * @return bool
	 */
	public function put($uuid, $filePath, $fileName) {
		$path = $this->pathGenerator->generatePath($uuid, $fileName);
		return $this->mount->put($path, $this->getFileContent($filePath));
	}

	/**
	 * @param $uuid
	 * @param $stream
	 * @param $fileName
	 * @return mixed
	 */
	public function putStream($uuid, $stream, $fileName) {
		$path = $this->pathGenerator->generatePath($uuid, $fileName);
		return $this->mount->putStream($path, $stream);
	}

	public function copy($sourceUuid, $targetUuid, $fileName) {
		$sourcePath = $this->pathGenerator->generatePath($sourceUuid, $fileName);
		$targetPath = $this->pathGenerator->generatePath($targetUuid, $fileName);

		return $this->mount->copy($sourcePath, $targetPath);
	}

	/**
	 *
	 * Delete a file.
	 *
	 * @param string $uuid
	 * @param $fileName
	 * @return bool
	 */
	public function delete($uuid, $fileName) {
		$path = $this->pathGenerator->generatePath($uuid, $fileName);
		return $this->mount->delete($path);
	}

	/**
	 * @return mixed
	 */
	public function getMountType() {
		return static::MOUNT_TYPE;
	}

	/**
	 * @param string $uuid
	 * @param $fileName
	 * @return string
	 */
	public function getPath($uuid, $fileName) {
		return $this->pathGenerator->generatePath($uuid, $fileName);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function getFileContent($path){
		return file_get_contents($path);
	}
}