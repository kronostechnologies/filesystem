<?php

namespace Kronos\FileSystem;

use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Metadata;

interface FileRepositoryInterface {

	/**
	 * @param string $mountType
	 * @return int
	 */
	public function add($mountType);

	/**
	 * @param int $uuid
	 * @return string
	 */
	public function getFileMountType($uuid);

	/**
	 * @param int $uuid
	 * @return bool
	 */
	public function delete($uuid);

	/**
	 * @param int $uuid
	 * @param string $mountType
	 * @return bool
	 */
	public function update($uuid,$mountType);
}