<?php

namespace Kronos\FileSystem\Adaptor;


use League\Flysystem\Config;

/**
 * Based on FlySystem Adapter interface for now.
 *
 * Interface AdaptorInterface
 * @package Kronos\FileSystem\Adaptor
 */
interface AdaptorInterface {

	/**
	 * Write a new file.
	 *
	 * @param string $path
	 * @param string $contents
	 * @param Configuration $config   Configuration object
	 *
	 * @return array|false false on failure file meta data on success
	 */
	public function write($path, $contents, Config $config);

	/**
	 * Write a new file using a stream.
	 *
	 * @param string   $path
	 * @param resource $resource
	 * @param Configuration   $config   Configuration object
	 *
	 * @return array|false false on failure file meta data on success
	 */
	public function writeStream($path, $resource, Config $config);

	/**
	 * Update a file.
	 *
	 * @param string $path
	 * @param string $contents
	 * @param Configuration $config   Configuration object
	 *
	 * @return array|false false on failure file meta data on success
	 */
	public function update($path, $contents, Config $config);

	/**
	 * Update a file using a stream.
	 *
	 * @param string   $path
	 * @param resource $resource
	 * @param Configuration   $config   Configuration object
	 *
	 * @return array|false false on failure file meta data on success
	 */
	public function updateStream($path, $resource, Config $config);

	/**
	 * Rename a file.
	 *
	 * @param string $path
	 * @param string $newpath
	 *
	 * @return bool
	 */
	public function rename($path, $newpath);

	/**
	 * Copy a file.
	 *
	 * @param string $path
	 * @param string $newpath
	 *
	 * @return bool
	 */
	public function copy($path, $newpath);

	/**
	 * Delete a file.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function delete($path);

	/**
	 * Delete a directory.
	 *
	 * @param string $dirname
	 *
	 * @return bool
	 */
	public function deleteDir($dirname);

	/**
	 * Create a directory.
	 *
	 * @param string $dirname directory name
	 * @param Configuration $config
	 *
	 * @return array|false
	 */
	public function createDir($dirname, Config $config);
}