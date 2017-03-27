<?php
namespace Kronos\FileSystem;

use Kronos\FileSystem\DTO\CopyDTO;
use Kronos\FileSystem\DTO\ImportationDTO;
use Kronos\FileSystem\DTO\MoveDTO;
use Kronos\FileSystem\Adaptor\AdaptorFactory;

class FileSystem implements FileSystemInterface {

	/**
	 * @var AdaptorFactory
	 */
	private $fileSystemAdaptorFactory;

	public function __construct(AdaptorFactory $fileSystemAdaptorFactory) {
		$this->fileSystemAdaptorFactory = $fileSystemAdaptorFactory;
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	public function exist($path) {

	}

	/**
	 * @param int $file
	 * @reutrn resource
	 */
	public function get($file) {

	}

	/**
	 * @param ImportationDTO $insertionFile
	 * @return int
	 */
	public function put(ImportationDTO $importationDTO){

	}

	/**
	 * @param string $path
	 * @return bool
	 */
	public function isFile($path) {

	}

	/**
	 * @param string $path
	 * @return bool
	 */
	public function isFolder($path) {

	}

	/**
	 * @param MoveDTO $moveDTO
	 */
	public function move(MoveDTO $moveDTO) {

	}

	/**
	 * @param CopyDTO $moveDTO
	 */
	public function copy(CopyDTO $moveDTO) {

	}

	/**
	 * @param int $file
	 * @return bool
	 */
	public function delete($file) {

	}
}