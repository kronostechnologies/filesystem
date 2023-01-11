<?php

namespace Kronos\FileSystem\Mount\S3;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use DateTime;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Kronos\FileSystem\Exception\CantRetreiveFileException;
use Kronos\FileSystem\File\Internal\Metadata;
use Kronos\FileSystem\Mount\FlySystemBaseMount;
use Kronos\FileSystem\Mount\PathGeneratorInterface;
use Kronos\FileSystem\PromiseFactory;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Util\MimeType;

class S3 extends FlySystemBaseMount
{

    const PRESIGNED_URL_LIFE_TIME = '+30 seconds';
    const MOUNT_TYPE = 'S3';
    const RESTORED_OBJECT_LIFE_TIME_IN_DAYS = 2;

    private AsyncAdapter $asyncAdapter;

    public function __construct(
        PathGeneratorInterface $pathGenerator,
        Filesystem $mount,
        PromiseFactory $factory = null,
        S3Factory $s3Factory = null
    ) {
        parent::__construct($pathGenerator, $mount, $factory);

        $s3factory = $s3Factory ?: new S3Factory();
        $this->asyncAdapter = $s3factory->createAsyncUploader($mount);
    }

    /**
     * @param Filesystem $mount
     * @return bool
     */
    protected function isFileSystemValid(Filesystem $mount)
    {
        return $mount->getAdapter() instanceof AwsS3Adapter;
    }

    /**
     * @param $uuid
     * @param $fileName
     * @param bool $forceDownload
     * @return mixed|string
     */
    public function getUrl($uuid, $fileName, $forceDownload = false)
    {
        /** @var AwsS3Adapter $awsS3Adaptor */
        $awsS3Adaptor = $this->mount->getAdapter();
        $s3Client = $awsS3Adaptor->getClient();

        $path = $this->pathGenerator->generatePath($uuid, $fileName);
        $location = $awsS3Adaptor->applyPathPrefix($path);

        $commandOptions = [
            'Bucket' => $awsS3Adaptor->getBucket(),
            'Key' => $location,
        ];

        if ($forceDownload) {
            $commandOptions['ResponseContentDisposition'] = $this->getRFC6266ContentDisposition($fileName);
        }

        $command = $s3Client->getCommand('GetObject', $commandOptions);
        $request = $s3Client->createPresignedRequest($command, self::PRESIGNED_URL_LIFE_TIME);

        return (string)$request->getUri();
    }

    /**
     * @param string $uuid
     * @param $fileName
     * @throws CantRetreiveFileException
     */
    public function retrieve($uuid, $fileName)
    {
        /** @var AwsS3Adapter $awsS3Adaptor */
        $awsS3Adaptor = $this->mount->getAdapter();
        $s3Client = $awsS3Adaptor->getClient();

        $path = $this->pathGenerator->generatePath($uuid, $fileName);
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
        } catch (S3Exception $exception) {
            throw new CantRetreiveFileException($uuid, $exception);
        }
    }

    /**
     * @param string $uuid
     * @param $fileName
     * @return Metadata|false
     */
    public function getMetadata($uuid, $fileName)
    {
        $path = $this->pathGenerator->generatePath($uuid, $fileName);

        if ($s3Metadata = $this->mount->getMetadata($path)) {
            $metadata = new Metadata();

            $metadata->size = isset($s3Metadata['size']) ? $s3Metadata['size'] : 0;
            $metadata->lastModifiedDate = new DateTime('@' . $s3Metadata['timestamp']);
            $metadata->mimetype = $s3Metadata['mimetype'];

            return $metadata;
        }

        return false;
    }

    public function deleteAsync(
        $uuid,
        $filename
    ): PromiseInterface {
        /** @var AwsS3Adapter $s3Adaptor */
        $s3Adaptor = $this->mount->getAdapter();

        /** @var S3Client $s3Client */
        $s3Client = $s3Adaptor->getClient();

        $path = $this->pathGenerator->generatePath($uuid, $filename);
        $prefixedPath = $s3Adaptor->applyPathPrefix($path);
        $bucket = $s3Adaptor->getBucket();

        $command = $s3Client->getCommand(
            'deleteObject',
            [
                'Bucket' => $bucket,
                'Key' => $prefixedPath,
            ]
        );

        return $s3Client
            ->executeAsync($command)
            ->then(function($response) {
                // We dont care about the response but the method should return a bool if the file was actually deleted
                return true;
            });
    }

    /**
     * Write a new file using a stream.
     *
     * @param string $uuid
     * @param string $filePath
     * @param $fileName
     * @return bool
     */
    public function put($uuid, $filePath, $fileName)
    {
        $path = $this->pathGenerator->generatePath($uuid, $fileName);
        /** @psalm-suppress InternalClass, InternalMethod */
        $mimeType = MimeType::detectByFilename($fileName);
        return $this->mount->put($path, $this->getFileContent($filePath), ['ContentType' => $mimeType]);
    }

    public function putAsync($uuid, $filePath, $fileName): PromiseInterface
    {
        $path = $this->pathGenerator->generatePath($uuid, $fileName);
        $content = $this->getFileContent($filePath);
        return $this->asyncAdapter
            ->upload($path, $content)
            ->then(function($response) {
                return true;
            });
    }

    public function putStream($uuid, $stream, $fileName)
    {
        $path = $this->pathGenerator->generatePath($uuid, $fileName);
        /** @psalm-suppress InternalClass, InternalMethod */
        $mimeType = MimeType::detectByFilename($fileName);
        return $this->mount->putStream($path, $stream, ['ContentType' => $mimeType]);
    }

    public function putStreamAsync($uuid, $stream, $fileName): PromiseInterface
    {
        $path = $this->pathGenerator->generatePath($uuid, $fileName);
        return $this->asyncAdapter
            ->upload($path, $stream)
            ->then(function($response) {
                return true;
            });
    }

    public function copyAsync($sourceUuid, $targetUuid, $fileName): PromiseInterface
    {
        $sourcePath = $this->pathGenerator->generatePath($sourceUuid, $fileName);
        $targetPath = $this->pathGenerator->generatePath($targetUuid, $fileName);

        return $this->asyncAdapter
            ->copy($sourcePath, $targetPath)
            ->then(function($response) {
                return true;
            });
    }

    public function hasAsync($uuid, $fileName): PromiseInterface
    {
        $path = $this->pathGenerator->generatePath($uuid, $fileName);
        return $this->asyncAdapter->has($path);
    }
}
