<?php

namespace Kronos\FileSystem\Mount\Local;

use Kronos\FileSystem\Mount\MountInterface;
use Kronos\FileSystem\Mount\PathGeneratorInterface;
use League\Flysystem\Adapter\Local as LocalFlySystem;

class Local implements MountInterface {
	/**
	 * @var LocalFlySystem
	 */
	private $mount;

	public function __construct(PathGeneratorInterface $pathGenerator,LocalFlySystem $Mount) {
		$this->mount = $Mount;
	}

	/**
	 * @param string $path
	 * @return resource
	 */
	public function getRessource($path) {
		// TODO: Implement getRessource() method.
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function getSignedUrl($path) {
		// TODO: Implement getSignedUrl() method.
	}

	/**
	 * Write a new file using a stream.
	 *
	 * @param string $path
	 * @param resource $resource
	 * @param array $config Configuration object
	 *
	 * @return bool
	 */
	public function write($path, $resource, array $config) {
		// TODO: Implement write() method.
	}

	/**
	 * Update a file using a stream.
	 *
	 * @param string $path
	 * @param resource $resource
	 * @param array $config
	 *
	 * @return bool
	 */
	public function update($path, $resource, array $config) {
		// TODO: Implement update() method.
	}

	/**
	 * Delete a file.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function delete($path) {
		// TODO: Implement delete() method.
	}

	/**
	 * @return string
	 */
	public function getMountType() {
		// TODO: Implement getMountType() method.
	}
}