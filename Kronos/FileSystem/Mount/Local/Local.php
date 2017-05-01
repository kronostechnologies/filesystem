<?php

namespace Kronos\FileSystem\Mount\Local;

use DateTime;
use Kronos\FileSystem\Exception\CantRetreiveFileException;
use Kronos\FileSystem\File\Metadata;
use Kronos\FileSystem\Mount\FlySystemBaseMount;
use League\Flysystem\Adapter\Local as LocalFlySystem;
use League\Flysystem\Filesystem;

class Local extends FlySystemBaseMount {

	const MOUNT_TYPE = 'LOCAL';
	const SIGNED_URL_BASE_PATH = 'utils/get_document.php?id=';

	/**
	 * @param Filesystem $mount
	 * @return bool
	 */
	protected function isFileSystemValid(Filesystem $mount){
		return $mount->getAdapter() instanceof  LocalFlySystem;
	}

	/**
	 * @param string $uuid
	 * @return string
	 */
	public function getSignedUrl($uuid) {
		return self::SIGNED_URL_BASE_PATH.$uuid;
	}



	/**
	 * @param string $uuid
	 * @throws CantRetreiveFileException
	 */
	public function retrieve($uuid) {
		throw new CantRetreiveFileException($uuid);
	}

	/**
	 * @param string $uuid
	 * @return Metadata|false
	 */
	public function getMetadata($uuid) {
		$path = $this->pathGenerator->generatePath($uuid);

		if($localMetadata = $this->mount->getMetadata($path)){
			$metadata = new Metadata();

			$metadata->size = isset($localMetadata['size']) ?$localMetadata['size'] : 0 ;
			$metadata->lastModifiedDate = new DateTime('@' .$localMetadata['timestamp']);
			$metadata->mimetype = $this->mount->getMimetype($path);

			return $metadata;
		}

		return false;
	}
}