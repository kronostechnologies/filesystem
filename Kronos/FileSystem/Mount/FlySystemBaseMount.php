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
	 * @return File
	 */
	public function get($uuid) {
		$path = $this->pathGenerator->generatePath($uuid);
		$flySystemFile = $this->mount->get($path);
		return new File($flySystemFile);
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
	 * Delete a file.
	 *
	 * @param string $uuid
	 *
	 * @return bool
	 */
	public function delete($uuid) {
		$path = $this->pathGenerator->generatePath($uuid);
		return $this->mount->delete($path);

	}

	/**
	 * @return string
	 */
	public function getMountType() {
		return static::MOUNT_TYPE;
	}

	/**
	 * @param string $uuid
	 * @return string
	 */
	public function getPath($uuid) {
		return $this->pathGenerator->generatePath($uuid);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function getFileContent($path){
		return file_get_contents($path);
	}
}