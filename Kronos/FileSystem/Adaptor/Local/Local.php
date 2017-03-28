<?php

namespace Kronos\FileSystem\Adaptor\Local;

use League\Flysystem\Adapter\Local as LocalFlySystem;

class Local implements LocalAdaptorInterface{
	/**
	 * @var LocalFlySystem
	 */
	private $adaptor;

	public function __construct(LocalFlySystem $adaptor) {
		$this->adaptor = $adaptor;
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
	 * @param Configuration $config Configuration object
	 *
	 * @return bool
	 */
	public function write($path, $resource, Configuration $config) {
		// TODO: Implement write() method.
	}

	/**
	 * Update a file using a stream.
	 *
	 * @param string $path
	 * @param resource $resource
	 * @param Configuration $config
	 *
	 * @return bool
	 */
	public function update($path, $resource, Configuration $config) {
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
}