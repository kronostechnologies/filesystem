<?php

namespace Kronos\FileSystem\Mount;

/**
 *
 * Interface MountInterface
 * @package Kronos\FileSystem\Mount
 */
interface MountInterface {

	/**
	 * @param string $path
	 * @return resource
	 */
	public function getRessource($path);

	/**
	 * @param string $path
	 * @return string
	 */
	public function getSignedUrl($path);

	/**
	 * Delete a file.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function delete($path);


	/**
	 * Write a new file using a stream.
	 *
	 * @param string $path
	 * @param resource $resource
	 * @param array $config
	 *
	 * @return bool
	 */
	public function write($path, $resource, array $config);

	/**
	 * Update a file using a stream.
	 *
	 * @param string $path
	 * @param resource $resource
	 * @param array $config
	 *
	 * @return bool
	 */
	public function update($path, $resource, array $config);

	/**
	 * @return string
	 */
	public function getMountType();
}