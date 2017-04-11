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
	 * @param string $uuid
	 * @return resource
	 */
	public function getResource($uuid) {
		// TODO: Implement getResource() method.
	}

	/**
	 * @param string $uuid
	 * @return string
	 */
	public function getSignedUrl($uuid) {
		// TODO: Implement getSignedUrl() method.
	}

	/**
	 * Write a new file using a stream.
	 *
	 * @param string $uuid
	 * @param resource $resource
	 *
	 * @return bool
	 */
	public function write($uuid, $resource) {
		// TODO: Implement write() method.
	}

	/**
	 * Update a file using a stream.
	 *
	 * @param string $path
	 * @param resource $resource
	 *
	 * @return bool
	 */
	public function update($path, $resource) {
		// TODO: Implement update() method.
	}

	/**
	 * Delete a file.
	 *
	 * @param string $uuid
	 *
	 * @return bool
	 */
	public function delete($uuid) {
		// TODO: Implement delete() method.
	}

	/**
	 * @return string
	 */
	public function getMountType() {
		// TODO: Implement getMountType() method.
	}
}