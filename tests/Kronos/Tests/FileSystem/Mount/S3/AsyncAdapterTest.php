<?php

namespace Kronos\Tests\FileSystem\Mount\S3;

use Aws\S3\S3Client;
use GuzzleHttp\Promise\PromiseInterface;
use Kronos\FileSystem\Mount\S3\AsyncAdapter;
use Kronos\FileSystem\Mount\S3\ConfigToOptionsTranslator;
use Kronos\FileSystem\Mount\S3\SupportedOptionsEnum;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AsyncAdapterTest extends TestCase
{
    const PATH = 'path';
    const CONTENTS = 'contents';
    const STORAGE_CLASS = 'storage class';
    const BUCKET_NAME = 'bucket name';
    const PREFIXED_PATH = 'prefixed path';
    const PRIVATE = 'private';
    const PUBLIC_READ = 'public-read';
    const TEXT_PLAIN = 'text/plain';
    const SOURCE_PATH = 'source path';
    const PREFIXED_SOURCE_PATH = 'prefixed source path';
    const TARGET_PATH = 'target path';
    const PREFIXED_TARGET_PATH = 'prefixed target path';
    /**
     * @var Filesystem|MockObject
     */
    private $mount;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var AwsS3Adapter|MockObject
     */
    private $s3Adapter;

    /**
     * @var S3Client|MockObject
     */
    private $s3Client;

    /**
     * @var ConfigToOptionsTranslator|MockObject
     */
    private $configToOptionsTranslator;

    /**
     * @var AsyncAdapter
     */
    private $asyncAdapter;

    protected function setUp(): void
    {
        $this->s3Client = $this->createMock(S3Client::class);
        $this->s3Adapter = $this->createMock(AwsS3Adapter::class);
        $this->s3Adapter
            ->method('getClient')
            ->willReturn($this->s3Client);
        $this->mount = $this->createMock(Filesystem::class);
        $this->mount
            ->method('getAdapter')
            ->willReturn($this->s3Adapter);
    }

    public function test_mount_constructor_shouldGetAdaptor(): void
    {
        $this->mount
            ->expects(self::once())
            ->method('getAdapter');

        $this->asyncAdapter = new AsyncAdapter($this->mount);
    }

    public function test_adaptor_constructor_shouldGetS3Client(): void
    {
        $this->s3Adapter
            ->expects(self::once())
            ->method('getClient');

        $this->asyncAdapter = new AsyncAdapter($this->mount);
    }

    public function test_nonS3Adaptor_constructor_shouldThrowException(): void
    {
        $nonS3Adaptor = $this->createMock(Local::class);
        $this->mount = $this->createMock(Filesystem::class);
        $this->mount
            ->method('getAdapter')
            ->willReturn($nonS3Adaptor);
        $this->expectException(\RuntimeException::class);

        $this->asyncAdapter = new AsyncAdapter($this->mount);
    }

    public function test_path_upload_shouldApplyPathPrefix(): void
    {
        $this->givenSetup();
        $this->s3Adapter
            ->expects(self::once())
            ->method('applyPathPrefix')
            ->with(self::PATH);

        $this->asyncAdapter->upload(self::PATH, self::CONTENTS);
    }

    public function test_mount_upload_shouldGetConfig(): void
    {
        $this->givenSetup();
        $this->mount
            ->expects(self::once())
            ->method('getConfig');

        $this->asyncAdapter->upload(self::PATH, self::CONTENTS);
    }

    public function test_config_upload_shouldTranslateToOptionsArray(): void
    {
        $this->givenSetup();
        $this->configToOptionsTranslator
            ->expects(self::once())
            ->method('translate')
            ->with($this->config);

        $this->asyncAdapter->upload(self::PATH, self::CONTENTS);
    }

    public function test_s3Adapter_upload_shouldGetBucket(): void
    {
        $this->givenSetup();
        $this->s3Adapter
            ->expects(self::once())
            ->method('getBucket');

        $this->asyncAdapter->upload(self::PATH, self::CONTENTS);
    }

    public function test_prefixedPathAndOptionsAndBucket_upload_shouldUploadAsyncAndReturnPromise(): void
    {
        $expectedPromise = $this->createMock(PromiseInterface::class);
        $options = $this->givenOptions();
        $this->givenSetup(false);
        $this->configToOptionsTranslator
            ->method('translate')
            ->willReturn($options);
        $this->s3Adapter
            ->method('getBucket')
            ->willReturn(self::BUCKET_NAME);
        $this->s3Adapter
            ->method('applyPathPrefix')
            ->willReturn(self::PREFIXED_PATH);
        $this->s3Client
            ->expects(self::once())
            ->method('uploadAsync')
            ->with(
                self::BUCKET_NAME,
                self::PREFIXED_PATH,
                self::CONTENTS,
                self::PRIVATE,
                ['params' => $options]
            )
            ->willReturn($expectedPromise);

        $actualPromise = $this->asyncAdapter->upload(self::PATH, self::CONTENTS);

        $this->assertSame($expectedPromise, $actualPromise);
    }

    public function test_aclNotInOptions_upload_shouldSetAclToPrivate(): void
    {
        $options = $this->givenOptions();
        $this->givenFullSetup();
        $this->configToOptionsTranslator
            ->method('translate')
            ->willReturn($options);
        $this->s3Client
            ->expects(self::once())
            ->method('uploadAsync')
            ->with(
                self::BUCKET_NAME,
                self::PREFIXED_PATH,
                self::CONTENTS,
                self::PRIVATE,
                ['params' => $options]
            );

        $this->asyncAdapter->upload(self::PATH, self::CONTENTS);
    }

    public function test_aclInOptions_upload_shouldSetAclToOptionsValue(): void
    {
        $options = $this->givenOptions([SupportedOptionsEnum::ACL => self::PUBLIC_READ]);
        $this->givenFullSetup();
        $this->configToOptionsTranslator
            ->method('translate')
            ->willReturn($options);
        $this->s3Client
            ->expects(self::once())
            ->method('uploadAsync')
            ->with(
                self::BUCKET_NAME,
                self::PREFIXED_PATH,
                self::CONTENTS,
                self::PUBLIC_READ,
                ['params' => $options]
            );

        $this->asyncAdapter->upload(self::PATH, self::CONTENTS);
    }

    public function test_option_upload_shouldEnsureContentTypeLength(): void
    {
        $options = [];
        $this->givenFullSetup();
        $this->configToOptionsTranslator
            ->method('translate')
            ->willReturn($options);
        $this->s3Client
            ->expects(self::once())
            ->method('uploadAsync')
            ->with(
                self::BUCKET_NAME,
                self::PREFIXED_PATH,
                self::CONTENTS,
                self::PRIVATE,
                [
                    'params' => [
                        SupportedOptionsEnum::CONTENT_TYPE => self::TEXT_PLAIN,
                        SupportedOptionsEnum::CONTENT_LENGTH => strlen(self::CONTENTS),
                    ]
                ]
            );

        $this->asyncAdapter->upload(self::PATH, self::CONTENTS);
    }

    public function test_mount_copy_shouldGetConfig(): void
    {
        $this->givenSetup();
        $this->mount
            ->expects(self::once())
            ->method('getConfig');
        $this->s3Client
            ->method('copyAsync')
            ->willReturn($this->createMock(PromiseInterface::class));

        $this->asyncAdapter->copy(self::SOURCE_PATH, self::TARGET_PATH);
    }

    public function test_config_copy_shouldTranslateToOptionsArray(): void
    {
        $this->givenSetup();
        $this->configToOptionsTranslator
            ->expects(self::once())
            ->method('translate')
            ->with($this->config);
        $this->s3Client
            ->method('copyAsync')
            ->willReturn($this->createMock(PromiseInterface::class));

        $this->asyncAdapter->copy(self::SOURCE_PATH, self::TARGET_PATH);
    }

    public function test_sourceAndTargetPaths_copy_shouldApplyPathPrefix(): void
    {
        $this->givenSetup();
        $this->s3Adapter
            ->expects(self::exactly(2))
            ->method('applyPathPrefix')
            ->withConsecutive(
                [self::SOURCE_PATH],
                [self::TARGET_PATH]
            )
            ->willReturnMap([
                [self::SOURCE_PATH, self::PREFIXED_SOURCE_PATH],
                [self::TARGET_PATH, self::PREFIXED_TARGET_PATH]
            ]);
        $this->s3Client
            ->method('copyAsync')
            ->willReturn($this->createMock(PromiseInterface::class));

        $this->asyncAdapter->copy(self::SOURCE_PATH, self::TARGET_PATH);
    }

    public function test_s3Adaptor_copy_shouldGetBucket(): void
    {
        $this->givenSetup();
        $this->s3Adapter
            ->expects(self::once())
            ->method('getBucket')
            ->willReturn(self::BUCKET_NAME);
        $this->s3Client
            ->method('copyAsync')
            ->willReturn($this->createMock(PromiseInterface::class));

        $this->asyncAdapter->copy(self::SOURCE_PATH, self::TARGET_PATH);
    }

    public function test_optionsBucketAndPaths_copy_shouldCopyAsyncAndReturnPromise(): void
    {
        $options = [ 'some option' => 'some value' ];
        $this->givenSetup();
        $this->configToOptionsTranslator
            ->method('translate')
            ->willReturn($options);
        $this->s3Adapter
            ->method('getBucket')
            ->willReturn(self::BUCKET_NAME);
        $this->s3Adapter
            ->method('applyPathPrefix')
            ->willReturnMap([
                [self::SOURCE_PATH, self::PREFIXED_SOURCE_PATH],
                [self::TARGET_PATH, self::PREFIXED_TARGET_PATH]
            ]);
        $expectedPromise = $this->createMock(PromiseInterface::class);
        $this->s3Client
            ->expects(self::once())
            ->method('copyAsync')
            ->with(
                self::BUCKET_NAME,
                self::PREFIXED_SOURCE_PATH,
                self::BUCKET_NAME,
                self::PREFIXED_TARGET_PATH,
                'private',
                $options
            )
            ->willReturn($expectedPromise);

        $actualPromise = $this->asyncAdapter->copy(self::SOURCE_PATH, self::TARGET_PATH);

        self::assertSame($expectedPromise, $actualPromise);
    }

    protected function givenSetup($withPromise = true): void
    {
        $this->configToOptionsTranslator = $this->createMock(ConfigToOptionsTranslator::class);
        $this->asyncAdapter = new AsyncAdapter($this->mount, $this->configToOptionsTranslator);

        $this->config = $this->createMock(Config::class);
        $this->mount
            ->method('getConfig')
            ->willReturn($this->config);

        if ($withPromise) {
            $this->s3Client
                ->method('uploadAsync')
                ->willReturn($this->createMock(PromiseInterface::class));
        }
    }

    protected function givenFullSetup(): void
    {
        $this->givenSetup();

        $this->s3Adapter
            ->method('getBucket')
            ->willReturn(self::BUCKET_NAME);
        $this->s3Adapter
            ->method('applyPathPrefix')
            ->willReturn(self::PREFIXED_PATH);
    }

    protected function givenOptions($options = []): array
    {
        return array_merge(
            [
                SupportedOptionsEnum::CONTENT_TYPE => self::TEXT_PLAIN,
                SupportedOptionsEnum::CONTENT_LENGTH => strlen(self::CONTENTS)
            ],
            $options
        );
    }
}
