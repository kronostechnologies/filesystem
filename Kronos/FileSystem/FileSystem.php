<?php

namespace Kronos\FileSystem;

use Kronos\FileSystem\File\File;
use Kronos\FileSystem\Mount\Selector;

class BaseFileSystem implements FileSystemInterface {

	public function __construct(Selector $mountSelector) {
	}

	/**
	 * @param resource $file
	 * @return int
	 */
	public function put(resource $file){

	}

	/**
	 * @param int $id
	 * @return File
	 */
	public function get($id){

	}

	/**
	 * @param int $id
	 * @return string
	 */
	public function getDownloadableLink($id){

	}


	/**
	 * @param int $id
	 * @return File
	 */
	public function getMetadata($id){

	}

	/**
	 * @param int $id
	 */
	public function delete($id){

	}

	/**
	 * @param int $id
	 */
	public function retrieve($id) {

	}
}