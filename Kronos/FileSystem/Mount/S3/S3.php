<?php

namespace Kronos\FileSystem\Mount\S3;

use Kronos\FileSystem\Exception\CantRetreiveFileException;
use Kronos\FileSystem\Exception\WrongFileSystemTypeException;
use Kronos\FileSystem\File\Metadata;
use Kronos\FileSystem\Mount\MountInterface;
use Kronos\FileSystem\Mount\PathGeneratorInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Config;
use League\Flysystem\ConfigAwareTrait;
use League\Flysystem\Filesystem;

class S3 implements MountInterface  {

	const PRESIGNED_URL_LIFE_TIME = '+5 minutes';
	const MOUNT_TYPE = 'S3';

	/**
	 * @var Filesystem
	 */
	private $mount;
	/**
	 * @var PathGeneratorInterface
	 */
	private $pathGenerator;

	public function __construct(PathGeneratorInterface $pathGenerator,Filesystem $mount) {
		if(!$this->issAwsS3Mount($mount)){
			throw new WrongFileSystemTypeException($this->getMountType(),get_class($mount->getAdapter()));
		}
		$this->mount = $mount;
		$this->pathGenerator = $pathGenerator;
	}

	/**
	 * @param Filesystem $mount
	 * @return bool
	 */
	private function issAwsS3Mount(Filesystem $mount){
		return $mount->getAdapter() instanceof  AwsS3Adapter;
	}

	/**
	 * @param string $uuid
	 * @return resource|false
	 */
	public function getResource($uuid) {
		$path = $this->pathGenerator->generatePath($uuid);
		return $this->mount->readStream($path);
	}

	/**
	 * @param string $uuid
	 * @return string
	 */
	public function getSignedUrl($uuid) {
		/** @var AwsS3Adapter $awsS3Adaptor */
		$awsS3Adaptor = $this->mount->getAdapter();
		$s3Client = $awsS3Adaptor->getClient();

		$path = $this->pathGenerator->generatePath($uuid);
		$location = $awsS3Adaptor->applyPathPrefix($path);

		$command = $s3Client->getCommand(
			'GetObject',
			[
				'Bucket' => $awsS3Adaptor->getBucket(),
				'Key' => $location,
			]
		);

		$request = $s3Client->createPresignedRequest($command, self::PRESIGNED_URL_LIFE_TIME);

		$presignedUrl = (string) $request->getUri();
		return $presignedUrl;
	}

	/**
	 * Write a new file using a stream.
	 *
	 * @param string $uuid
	 * @param resource $resource
	 *
	 * @return bool
	 */
	public function write($uuid, $resource) {
		$path = $this->pathGenerator->generatePath($uuid);
		return $this->mount->writeStream($path,$resource);
	}

	/**
	 * Update a file using a stream.
	 *
	 * @param string $uuid
	 * @param resource $resource
	 *
	 * @return bool
	 */
	public function update($uuid, $resource) {
		return false;
	}

	/**
	 * Delete a file.
	 *
	 * @param string $uuid
	 *
	 * @return bool
	 */
	public function delete($uuid) {
		$path = $this->pathGenerator->generatePath($uuid);
		return $this->mount->delete($path);

	}

	/**
	 * @return string
	 */
	public function getMountType() {
		return self::MOUNT_TYPE;
	}

	/**
	 * @param string $uuid
	 * @throws CantRetreiveFileException
	 */
	public function retrieve($uuid) {
		throw new CantRetreiveFileException();
	}

	/**
	 * @param string $uuid
	 * @return array|false
	 */
	public function getMetadata($uuid) {
		$path = $this->pathGenerator->generatePath($uuid);
		return $this->mount->getMetadata($path);
	}
}