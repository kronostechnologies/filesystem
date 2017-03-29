<?php
/**
 * Created by PhpStorm.
 * User: mdemers
 * Date: 2017-03-28
 * Time: 9:27 AM
 */

namespace Kronos\FileSystem\Service;


use Kronos\FileSystem\Service\Adaptor\AdaptorFactory;
use Kronos\FileSystem\Service\Exception\FunctionnalityNotAvailable;
use Kronos\FileSystem\File\File;

abstract class BaseFileSystem implements FileSystemInterface {
	/**
	 * @var AdaptorFactory
	 */
	private $fileSystemAdaptorFactory;

	public function __construct(AdaptorFactory $fileSystemAdaptorFactory) {
		$this->fileSystemAdaptorFactory = $fileSystemAdaptorFactory;
	}

	/**
	 * @param File $id
	 * @return int
	 * @throws FunctionnalityNotAvailable
	 */
	public function put(File $file){
		throw new FunctionnalityNotAvailable('`put`');
	}

	/**
	 * @param int $id
	 * @return File
	 * @throws FunctionnalityNotAvailable
	 */
	public function get($id){
		throw new FunctionnalityNotAvailable('`get`');
	}

	/**
	 * @param int $id
	 * @return File
	 * @throws FunctionnalityNotAvailable
	 */
	public function getMetadata($id){
		throw new FunctionnalityNotAvailable('`getMetadata`');
	}

	/**
	 * @param int $id
	 * @throws FunctionnalityNotAvailable
	 */
	public function delete($id){
		throw new FunctionnalityNotAvailable('`delete`');
	}

	/**
	 * @param int $id
	 * @throws FunctionnalityNotAvailable
	 */
	public function retrieve($id) {
		throw new FunctionnalityNotAvailable('`retreive`');
	}
}