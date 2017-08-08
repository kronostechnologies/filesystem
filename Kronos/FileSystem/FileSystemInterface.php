<?php

namespace Kronos\FileSystem;

use Kronos\FileSystem\Exception\FileCantBeWrittenException;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Metadata;

interface FileSystemInterface {

	/**
	 * @param string $filePath
	 * @param string $fileName
	 * @return int
	 * @throws FileCantBeWrittenException
	 */
	public function put($filePath, $fileName);

	/**
	 * @param int $id
	 * @return File
	 */
	public function get($id, $fileName);

	/**
	 * @param int $id
	 * @return string
	 */
	public function getDownloadableLink($id, $fileName);

	/**
	 * @param int $id
	 * @return Metadata
	 */
	public function getMetadata($id, $fileName);

	/**
	 * @param int $id
	 */
	public function delete($id, $fileName);

	/**
	 * @param int $id
	 */
	public function retrieve($id, $fileName);
}
