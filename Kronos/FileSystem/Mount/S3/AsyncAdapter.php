<?php

namespace Kronos\FileSystem\Mount\S3;

use Aws\S3\S3Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use League\Flysystem\Util;
use \RuntimeException;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

class AsyncAdapter
{
    /**
     * @var Filesystem
     */
    private $mount;

    /**
     * @var AwsS3Adapter;
     */
    private $s3Adapter;

    /**
     * @var S3Client
     */
    private $s3Client;

    /**
     * @var ConfigToOptionsTranslator
     */
    private $configTranslator;

    /**
     * AsyncUploader constructor.
     * @param Filesystem $mount
     * @param ConfigToOptionsTranslator $configTranslator
     */
    public function __construct(Filesystem $mount, ConfigToOptionsTranslator $configTranslator = null)
    {
        $this->mount = $mount;
        $adapter = $this->mount->getAdapter();
        if ($adapter instanceof AwsS3Adapter) {
            $this->s3Adapter = $adapter;
            $this->s3Client = $this->s3Adapter->getClient();
        } else {
            throw new RuntimeException('Expecting AwsS3Adaptor based Flysystem Filesystem');
        }

        $this->configTranslator = $configTranslator ?? new ConfigToOptionsTranslator();
    }

    public function upload(string $path, $content): PromiseInterface
    {
        $options = $this->configTranslator->translate($this->mount->getConfig());
         if ( ! isset($options['ContentType'])) {
            $options['ContentType'] = Util::guessMimeType($path, $content);
        }

        if ( ! isset($options['ContentLength'])) {
            $options['ContentLength'] = is_resource($content) ? Util::getStreamSize($content) : Util::contentSize($content);
        }

        if ($options['ContentLength'] === null) {
            unset($options['ContentLength']);
        }

         $acl = $options[SupportedOptionsEnum::ACL] ?? 'private';

         return $this->s3Client->uploadAsync(
            $this->s3Adapter->getBucket(),
            $this->s3Adapter->applyPathPrefix($path),
            $content,
            $acl,
            ['params' => $options]
         );
    }

    public function copy(string $sourcePath, string $targetPath): PromiseInterface
    {
        $bucketName = $this->s3Adapter->getBucket();

        return $this->s3Client->copyAsync(
            $bucketName,
            $this->s3Adapter->applyPathPrefix($sourcePath),
            $bucketName,
            $this->s3Adapter->applyPathPrefix($targetPath),
            'private',
            $this->configTranslator->translate($this->mount->getConfig())
        );
    }
}
