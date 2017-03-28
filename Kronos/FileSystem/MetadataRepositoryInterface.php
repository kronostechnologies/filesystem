<?php

namespace Kronos\FileSystem;

use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Metadata;

interface MetadataRepositoryInterface {

	/**
	 * @param $id
	 * @return Metadata
	 */
	public function getMetadata($id);

	/**
	 * @param File $file
	 * @return int
	 */
	public function add(File $file);

	/**
	 * @param int $id
	 * @param File $file
	 * @return bool
	 */
	public function update($id,File $file);

	/**
	 * @param int $id
	 * @return bool
	 */
	public function delete($id);
}