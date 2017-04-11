<?php

namespace Kronos\FileSystem;

use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Metadata;

interface FileSystemInterface {

	/**
	 * @param resource $file
	 * @return int
	 */
	public function put($file);

	/**
	 * @param int $id
	 * @return File
	 */
	public function get($id);

	/**
	 * @param int $id
	 * @return string
	 */
	public function getDownloadableLink($id);

	/**
	 * @param int $id
	 * @return Metadata
	 */
	public function getMetadata($id);

	/**
	 * @param int $id
	 */
	public function delete($id);

	/**
	 * @param int $id
	 */
	public function retrieve($id);
}
