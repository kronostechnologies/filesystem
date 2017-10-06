<?php

namespace Kronos\FileSystem\Mount;
use Kronos\FileSystem\Exception\CantRetreiveFileException;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Internal\Metadata;
/**
 *
 * Interface MountInterface
 * @package Kronos\FileSystem\Mount
 */
interface MountInterface {

	/**
	 * @param string $uuid
	 * @return File
	 */
	public function get($uuid, $fileName);

	/**
	 * @param string $uuid
	 * @return string
	 */
	public function getUrl($uuid, $fileName);

	/**
	 * Delete a file.
	 *
	 * @param string $uuid
	 */
	public function delete($uuid, $fileName);


	/**
	 * Write a new file using a stream.
	 *
	 * @param $uuid
	 * @param $filePath
	 * @param $fileName
	 * @return mixed
	 *
	 */
	public function put($uuid, $filePath, $fileName);

	/**
	 * @param string $uuid
	 * @throws CantRetreiveFileException
	 */
	public function retrieve($uuid, $fileName);

	/**
	 * @param string $uuid
	 * @return mixed
	 */
	public function getPath($uuid, $fileName);

	/**
	 * @param string $uuid
	 * @return Metadata
	 */
	public function getMetadata($uuid, $fileName);

	/**
	 * @return string
	 */
	public function getMountType();
}