<?php

namespace Kronos\FileSystem;

use Kronos\FileSystem\Exception\FileCantBeWrittenException;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Metadata;

interface FileSystemInterface {

	/**
	 * @param string $filePath
	 * @param int $fileName
	 * @return mixed
	 */
	public function put($filePath, $fileName);

	/**
	 * @param int $id
	 * @return File
	 */
	public function get($id);

	/**
	 * @param int $id
	 * @return string
	 */
	public function getUrl($id);

	/**
	 * @param int $id
	 * @return Metadata
	 */
	public function getMetadata($id);

	/**
	 * @param string $id
	 * @return string
	 */
	public function copy($id);

	/**
	 * @param int $id
	 */
	public function delete($id);

	/**
	 * @param int $id
	 */
	public function retrieve($id);
}
