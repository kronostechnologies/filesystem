<?php

namespace Kronos\FileSystem\Mount\S3;

use Aws\S3\Exception\S3Exception;
use DateTime;
use Kronos\FileSystem\Exception\CantRetreiveFileException;
use Kronos\FileSystem\File\Metadata;
use Kronos\FileSystem\Mount\FlySystemBaseMount;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

class S3 extends FlySystemBaseMount {

	const PRESIGNED_URL_LIFE_TIME = '+5 minutes';
	const MOUNT_TYPE = 'S3';
	const RESTORED_OBJECT_LIFE_TIME_IN_DAYS = 2;

	/**
	 * @param Filesystem $mount
	 * @return bool
	 */
	protected function isFileSystemValid(Filesystem $mount) {
		return $mount->getAdapter() instanceof AwsS3Adapter;
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

		$presignedUrl = (string)$request->getUri();
		return $presignedUrl;
	}

	/**
	 * @param string $uuid
	 * @throws CantRetreiveFileException
	 */
	public function retrieve($uuid) {
		/** @var AwsS3Adapter $awsS3Adaptor */
		$awsS3Adaptor = $this->mount->getAdapter();
		$s3Client = $awsS3Adaptor->getClient();

		$path = $this->pathGenerator->generatePath($uuid);
		$location = $awsS3Adaptor->applyPathPrefix($path);

		try {
			$command = $s3Client->getCommand(
				'restoreObject',
				[
					'Bucket' => $awsS3Adaptor->getBucket(),
					'Key' => $location,
					'RestoreRequest' => [
						'Days' => self::RESTORED_OBJECT_LIFE_TIME_IN_DAYS,
						'GlacierJobParameters' => [
							'Tier' => 'Standard'
						]
					]
				]
			);

			$s3Client->execute($command);
		}
		catch(S3Exception $exception) {
			throw new CantRetreiveFileException($uuid, $exception);
		}
	}

	/**
	 * @param string $uuid
	 * @return Metadata|false
	 */
	public function getMetadata($uuid) {
		$path = $this->pathGenerator->generatePath($uuid);

		if($s3Metadata = $this->mount->getMetadata($path)) {
			$metadata = new Metadata();

			$metadata->size = isset($s3Metadata['size']) ? $s3Metadata['size'] : 0;
			$metadata->lastModifiedDate = new DateTime('@' . $s3Metadata['timestamp']);
			$metadata->mimetype = $s3Metadata['mimetype'];

			return $metadata;
		}

		return false;
	}
}