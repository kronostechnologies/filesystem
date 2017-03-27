<?php

namespace Kronos\FileSystem;


use Kronos\FileSystem\DTO\CopyDTO;
use Kronos\FileSystem\DTO\File;
use Kronos\FileSystem\DTO\FileMetadata;
use Kronos\FileSystem\DTO\ImportationDTO;
use Kronos\FileSystem\DTO\MoveDTO;

interface FileSystemInterface {

	/**
	 * @param string $path
	 * @return bool
	 */
	public function exist($path);

	/**
	 * @param int $file
	 * @return File
	 */
	public function get($file);

	/**
	 * @param int $file
	 * @return FileMetadata
	 */
	public function getMetadata($file);

	/**
	 * @param ImportationDTO $insertionFile
	 * @return int
	 */
	public function put(ImportationDTO $importationDTO);

	/**
	 * @param MoveDTO $moveDTO
	 */
	public function move(MoveDTO $moveDTO);

	/**
	 * @param CopyDTO $moveDTO
	 */
	public function copy(CopyDTO $moveDTO);

	/**
	 * @param $file
	 * @return bool
	 */
	public function delete($file);
}
