<?php

namespace Kronos\FileSystem\Service;

use Kronos\FileSystem\Service\Exception\FunctionnalityNotAvailable;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Metadata;

interface FileSystemInterface {

	/**
	 * @param File $id
	 * @return int
	 * @throws FunctionnalityNotAvailable
	 */
	public function put(File $file);

	/**
	 * @param int $id
	 * @return File
	 * @throws FunctionnalityNotAvailable
	 */
	public function get($id);

	/**
	 * @param int $id
	 * @return Metadata
	 * @throws FunctionnalityNotAvailable
	 */
	public function getMetadata($id);

	/**
	 * @param int $id
	 * @throws FunctionnalityNotAvailable
	 */
	public function delete($id);

	/**
	 * @param int $id
	 * @throws FunctionnalityNotAvailable
	 */
	public function retrieve($id);
}
