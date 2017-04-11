<?php

namespace Kronos\FileSystem\Mount;
use Kronos\FileSystem\Exception\CantRetreiveFileException;
use Kronos\FileSystem\File\Metadata;

/**
 *
 * Interface MountInterface
 * @package Kronos\FileSystem\Mount
 */
interface MountInterface {

	/**
	 * @param string $uuid
	 * @return resource
	 */
	public function getResource($uuid);

	/**
	 * @param string $uuid
	 * @return string
	 */
	public function getSignedUrl($uuid);

	/**
	 * Delete a file.
	 *
	 * @param string $uuid
	 */
	public function delete($uuid);


	/**
	 * Write a new file using a stream.
	 *
	 * @param string $uuid
	 * @param resource $resource
	 *
	 * @return bool
	 */
	public function write($uuid, $resource);

	/**
	 * Update a file using a stream.
	 *
	 * @param string $uuid
	 * @param resource $resource
	 *
	 * @return bool
	 */
	public function update($uuid, $resource);

	/**
	 * @param string $uuid
	 * @throws CantRetreiveFileException
	 */
	public function retrieve($uuid);

	/**
	 * @param string $uuid
	 * @return Metadata
	 */
	public function getMetadata($uuid);

	/**
	 * @return string
	 */
	public function getMountType();
}