<?php

namespace Kronos\FileSystem;


use Kronos\FileSystem\DTO\CopyDTO;
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
	 * @reutrn resource
	 */
	public function get($file);

	/**
	 * @param ImportationDTO $insertionFile
	 * @return int
	 */
	public function put(ImportationDTO $importationDTO);

	/**
	 * @param string $path
	 * @return bool
	 */
	public function isFile($path);

	/**
	 * @param string $path
	 * @return bool
	 */
	public function isFolder($path);

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
