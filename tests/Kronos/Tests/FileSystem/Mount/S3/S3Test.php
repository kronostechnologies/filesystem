<?php

namespace Kronos\Tests\FileSystem\Mount\S3;

use Aws\CommandInterface;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Closure;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Kronos\FileSystem\Exception\CantRetreiveFileException;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Internal\Metadata;
use Kronos\FileSystem\Mount\S3\AsyncAdapter;
use Kronos\FileSystem\Mount\S3\S3Factory;
use Kronos\FileSystem\PromiseFactory;
use Kronos\FileSystem\Mount\PathGeneratorInterface;
use Kronos\FileSystem\Mount\S3\S3;
use Kronos\Tests\FileSystem\ExtendedTestCase;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Util\MimeType;
use League\MimeTypeDetection\MimeTypeDetector;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\RequestInterface;

class S3Test extends ExtendedTestCase
{
    const UUID = 'UUID';
    const A_PATH = 'A_PATH';
    const A_FILE_NAME = 'A_FILE_NAME';
    const A_FILE_NAME_WITH_DOUBLE_QUOTES_AND_SPECIAL_CHARACTER = 'T"ESTà0';
    const A_FILE_NAME_WITH_DOUBLE_QUOTES_AND_SPECIAL_CHARACTER_ESCAPED = '"T\"ESTa0";filename*=UTF-8\'fr\'T%22EST%C3%A00';
    const A_FILE_PATH = 'A_FILE_PATH';
    const A_LOCATION = 'A_LOCATION';
    const S3_BUCKET = 'S3_BUCKET';
    const AN_URI = 'AN_URI';
    const A_FILE_CONTENT = 'A_FILE_CONTENT';
    const PUT_RESULT = self::HAS_RESULT;
    const SOURCE_UUID = 'source uuid';
    const TARGET_UUID = 'target uuid';
    const SOURCE_PATH = 'source path';
    const TARGET_PATH = 'target path';
    const COPY_RESULT = self::HAS_RESULT;
    const HAS_RESULT = true;
    const PREFIXED_PATH = 'prefixed path';
    const MIME_TYPE = 'mime/type';

    private PathGeneratorInterface&MockObject $pathGenerator;
    private AwsS3Adapter&MockObject $s3Adapter;
    private FileSystem&MockObject $fileSystem;
    private S3Client&MockObject $s3Client;
    private S3 $s3mount;
    private PromiseFactory&MockObject $promiseFactory;
    private S3Factory&MockObject $factory;
    private AsyncAdapter&MockObject $asyncAdapter;
    private MimeTypeDetector&MockObject $mimeTypeDetector;

    public function setUp(): void
    {
        $this->fileSystem = $this->createMock(Filesystem::class);
        $this->s3Client = $this->createMock(S3Client::class);
        $this->s3Adapter = $this->createMock(AwsS3Adapter::class);
        $this->pathGenerator = $this->createMock(PathGeneratorInterface::class);
        $this->promiseFactory = $this->createMock(PromiseFactory::class);
        $this->factory = $this->createMock(S3Factory::class);
        $this->asyncAdapter = $this->createMock(AsyncAdapter::class);

        $this->fileSystem->method('getAdapter')->willReturn($this->s3Adapter);
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->factory->method('createAsyncUploader')->willReturn($this->asyncAdapter);

        $this->mimeTypeDetector = $this->createMock(MimeTypeDetector::class);
        MimeType::useDetector($this->mimeTypeDetector);

        $this->s3mount = new s3MountTestable($this->pathGenerator, $this->fileSystem, $this->promiseFactory, $this->factory);
    }

    public function test_mount_constructor_shouldCreateAsyncUploader(): void
    {
        $this->factory
            ->expects(self::once())
            ->method('createAsyncUploader')
            ->with($this->fileSystem);

        $this->s3mount = new s3MountTestable($this->pathGenerator, $this->fileSystem, $this->promiseFactory, $this->factory);
    }

    public function test_uuid_get_shouldGetPathOfFile()
    {
        $this->fileSystem->method('get')->willReturn($this->createMock(\League\Flysystem\File::class));
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->s3mount->get(self::UUID, self::A_FILE_NAME);
    }

    public function test_path_get_shouldGet()
    {
        $this->fileSystem->method('get')->willReturn($this->createMock(\League\Flysystem\File::class));
        $this->givenGeneratedPath();

        $this->fileSystem
            ->expects(self::once())
            ->method('get')
            ->with(self::A_PATH);

        $this->s3mount->get(self::UUID, self::A_FILE_NAME);
    }

    public function test_stream_get_shouldReturnFile()
    {
        $this->fileSystem->method('get')->willReturn($this->createMock(\League\Flysystem\File::class));

        $file = $this->s3mount->get(self::UUID, self::A_FILE_NAME);

        self::assertInstanceOf(File::class, $file);
    }

    public function test_getSignedUrl_shouldGetS3Client()
    {
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));
        $this->s3Client->method('createPresignedRequest')->willReturn($this->createMock(RequestInterface::class));

        $this->s3Adapter
            ->expects(self::once())
            ->method('getClient');

        $this->s3mount->getUrl(self::UUID, self::A_FILE_NAME);
    }

    public function test_uuid_getSignedUrl_shouldGetPathOfFile()
    {
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));
        $this->s3Client->method('createPresignedRequest')->willReturn($this->createMock(RequestInterface::class));

        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->s3mount->getUrl(self::UUID, self::A_FILE_NAME);
    }

    public function test_path_getSignedUrl_shouldGetFileLocation()
    {
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));
        $this->s3Client->method('createPresignedRequest')->willReturn($this->createMock(RequestInterface::class));
        $this->givenGeneratedPath();

        $this->s3Adapter
            ->expects(self::once())
            ->method('applyPathPrefix')
            ->with(self::A_PATH);

        $this->s3mount->getUrl(self::UUID, self::A_FILE_NAME);
    }

    public function test_location_getSignedUrl_shouldGetCommandToGetFile()
    {
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Adapter->method('applyPathPrefix')->willReturn(self::A_LOCATION);
        $this->s3Adapter->method('getBucket')->willReturn(self::S3_BUCKET);
        $this->s3Client->method('createPresignedRequest')->willReturn($this->createMock(RequestInterface::class));

        $this->s3Client
            ->expects(self::once())
            ->method('getCommand')
            ->with(
                'GetObject',
                [
                    'Bucket' => self::S3_BUCKET,
                    'Key' => self::A_LOCATION,
                ]
            )
            ->willReturn($this->createMock(CommandInterface::class));

        $this->s3mount->getUrl(self::UUID, self::A_FILE_NAME);
    }

    public function test_locationAndForceDownload_getSignedUrl_shouldAddResponseContentDispositionToCommand()
    {
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Adapter->method('applyPathPrefix')->willReturn(self::A_LOCATION);
        $this->s3Adapter->method('getBucket')->willReturn(self::S3_BUCKET);
        $this->s3Client->method('createPresignedRequest')->willReturn($this->createMock(RequestInterface::class));

        $this->s3Client
            ->expects(self::once())
            ->method('getCommand')
            ->with(
                'GetObject',
                [
                    'Bucket' => self::S3_BUCKET,
                    'Key' => self::A_LOCATION,
                    'ResponseContentDisposition' => 'attachment;filename=' . self::A_FILE_NAME,
                ]
            )
            ->willReturn($this->createMock(CommandInterface::class));

        $this->s3mount->getUrl(self::UUID, self::A_FILE_NAME, true);
    }

    public function test_locationAndForceDownloadWithDocumentHavingDoubleQuotes_getSignedUrl_shouldAddResponseContentDispositionToCommandWithDoubleQuotesEscaped(
    )
    {
        if (PHP_OS == "Darwin") {
            $this->markTestSkipped("This test is broken on Darwin/macOS because libiconv implementation differs from GNU and handles 'à' as '`a' instead of 'a' when encoding in ASCII");
            return;
        }

        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Adapter->method('applyPathPrefix')->willReturn(self::A_LOCATION);
        $this->s3Adapter->method('getBucket')->willReturn(self::S3_BUCKET);
        $this->s3Client->method('createPresignedRequest')->willReturn($this->createMock(RequestInterface::class));

        $this->s3Client
            ->expects(self::once())
            ->method('getCommand')
            ->with(
                'GetObject',
                [
                    'Bucket' => self::S3_BUCKET,
                    'Key' => self::A_LOCATION,
                    'ResponseContentDisposition' => 'attachment;filename=' . self::A_FILE_NAME_WITH_DOUBLE_QUOTES_AND_SPECIAL_CHARACTER_ESCAPED,
                ]
            )
            ->willReturn($this->createMock(CommandInterface::class));

        $this->s3mount->getUrl(self::UUID, self::A_FILE_NAME_WITH_DOUBLE_QUOTES_AND_SPECIAL_CHARACTER, true);
    }

    public function test_command_getSignedUrl_shouldGetCommandToGetFile()
    {
        $command = $this->createMock(CommandInterface::class);
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($command);

        $this->s3Client
            ->expects(self::once())
            ->method('createPresignedRequest')
            ->with($command, S3::PRESIGNED_URL_LIFE_TIME)
            ->willReturn($this->createMock(RequestInterface::class));

        $this->s3mount->getUrl(self::UUID, self::A_FILE_NAME);
    }

    public function test_request_getSignedUrl_shouldGetUriFromRequest()
    {
        $request = $this->createMock(RequestInterface::class);
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));
        $this->s3Client->method('createPresignedRequest')->willReturn($request);

        $request
            ->expects(self::once())
            ->method('getUri');

        $this->s3mount->getUrl(self::UUID, self::A_FILE_NAME);
    }

    public function test_uri_getSignedUrl_shouldReturnUri()
    {
        $uri = new Uri(self::AN_URI);
        $request = $this->createMock(RequestInterface::class);
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));
        $this->s3Client->method('createPresignedRequest')->willReturn($request);
        $request->method('getUri')->willReturn($uri);

        $actualUri = $this->s3mount->getUrl(self::UUID, self::A_FILE_NAME);

        self::assertSame(self::AN_URI, $actualUri);
    }

    public function test_uuid_put_shouldGetPathOfFile()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->s3mount->put(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);
    }

    public function test_filename_put_shouldGetMimeType()
    {
        $this->mimeTypeDetector
            ->expects(self::once())
            ->method('detectMimeTypeFromPath')
            ->with(self::A_FILE_NAME)
            ->willReturn(self::MIME_TYPE);

        $this->s3mount->put(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);
    }

    public function test_pathAndMimeType_put_shouldPut()
    {
        $this->givenGeneratedPath();
        $this->mimeTypeDetector
            ->method('detectMimeTypeFromPath')
            ->willReturn(self::MIME_TYPE);

        $this->fileSystem
            ->expects(self::once())
            ->method('put')
            ->with(self::A_PATH, self::A_FILE_CONTENT, ['ContentType' => self::MIME_TYPE]);

        $this->s3mount->put(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);
    }

    public function test_FileWritten_put_shouldReturnTrue()
    {

        $this->fileSystem->method('put')->willReturn(self::HAS_RESULT);

        $written = $this->s3mount->put(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);

        $this->assertTrue($written);
    }

    public function test_FileNotWritten_put_shouldReturnFalse()
    {
        $this->fileSystem->method('put')->willReturn(false);

        $written = $this->s3mount->put(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);

        $this->assertFalse($written);
    }

    public function test_putStream_shouldGetPathOfFile()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->s3mount->putStream(self::UUID, self::A_FILE_CONTENT, self::A_FILE_NAME);
    }

    public function test_filename_putStream_shouldGetMimeType()
    {
        $this->mimeTypeDetector
            ->expects(self::once())
            ->method('detectMimeTypeFromPath')
            ->with(self::A_FILE_NAME)
            ->willReturn(self::MIME_TYPE);

        $this->s3mount->putStream(self::UUID, self::A_FILE_CONTENT, self::A_FILE_NAME);
    }

    public function test_pathAndMimeType_putStream_shouldPutAndReturnResult()
    {
        $stream = tmpfile();
        $this->givenGeneratedPath();
        $this->mimeTypeDetector
            ->method('detectMimeTypeFromPath')
            ->willReturn(self::MIME_TYPE);
        $this->fileSystem
            ->expects(self::once())
            ->method('putStream')
            ->with(self::A_PATH, $stream, ['ContentType' => self::MIME_TYPE])
            ->willReturn(self::PUT_RESULT);

        $actualResult = $this->s3mount->putStream(self::UUID, $stream, self::A_FILE_NAME);

        $this->assertSame(self::PUT_RESULT, $actualResult);
    }

    public function test_getMountType_ShouldReturnMountType()
    {
        $actualMountType = $this->s3mount->getMountType();

        self::assertEquals(S3::MOUNT_TYPE, $actualMountType);
    }

    public function test_uuid_delete_shouldGetPathOfFile()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID, self::A_FILE_NAME);

        $this->s3mount->delete(self::UUID, self::A_FILE_NAME);
    }

    public function test_path_delete_shouldDeleteFile()
    {
        $this->givenGeneratedPath();

        $this->fileSystem
            ->expects(self::once())
            ->method('delete')
            ->with(self::A_PATH);

        $this->s3mount->delete(self::UUID, self::A_FILE_NAME);
    }

    public function test_FileDeleted_delete_shouldReturnTrue()
    {

        $this->fileSystem->method('delete')->willReturn(self::HAS_RESULT);

        $deleted = $this->s3mount->delete(self::UUID, self::A_FILE_NAME);

        $this->assertTrue($deleted);
    }

    public function test_FileNotDeleted_delete_shouldReturnFalse()
    {
        $this->fileSystem->method('delete')->willReturn(false);

        $deleted = $this->s3mount->delete(self::UUID, self::A_FILE_NAME);

        $this->assertFalse($deleted);
    }

    public function test_uuid_deleteAsync_shouldGetPathOfFile()
    {
        $this->givenS3CommandAndPromise();
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID, self::A_FILE_NAME);

        $this->s3mount->deleteAsync(self::UUID, self::A_FILE_NAME);
    }

    public function test_uuid_deleteAsync_shouldGetAdaptor(): void
    {
        $this->givenS3CommandAndPromise();
        $this->fileSystem
            ->expects(self::once())
            ->method('getAdapter');

        $this->s3mount->deleteAsync(self::UUID, self::A_FILE_NAME);
    }

    public function test_path_deleteAsync_shouldApplyPathPrefix(): void
    {
        $this->givenS3CommandAndPromise();
        $this->givenGeneratedPath();
        $this->s3Adapter
            ->expects(self::once())
            ->method('applyPathPrefix')
            ->with(self::A_PATH);

        $this->s3mount->deleteAsync(self::UUID, self::A_FILE_NAME);
    }

    public function test_adaptor_deleteAsync_shouldGetS3Client(): void
    {
        $this->givenS3CommandAndPromise();
        $this->s3Adapter
            ->expects(self::once())
            ->method('getClient');

        $this->s3mount->deleteAsync(self::UUID, self::A_FILE_NAME);
    }

    public function test_adaptor_deleteAsync_shouldGetBucket(): void
    {
        $this->givenS3CommandAndPromise();
        $this->s3Adapter
            ->expects(self::once())
            ->method('getBucket');

        $this->s3mount->deleteAsync(self::UUID, self::A_FILE_NAME);
    }

    public function test_s3ClientAndPrefixedPath_deleteAsync_shouldDeleteObjectAsync(): void
    {
        $this->givenS3CommandAndPromise();
        $this->s3Adapter
            ->method('getBucket')
            ->willReturn(self::S3_BUCKET);
        $this->s3Adapter
            ->method('applyPathPrefix')
            ->willReturn(self::PREFIXED_PATH);
        $this->s3Client
            ->expects(self::once())
            ->method('getCommand')
            ->with(
                'deleteObject',
                [
                    'Bucket' => self::S3_BUCKET,
                    'Key' => self::PREFIXED_PATH
                ]
            );

        $this->s3mount->deleteAsync(self::UUID, self::A_FILE_NAME);
    }

    public function test_command_deleteAsync_shouldExecuteAsync(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $s3promise = new Promise();
        $this->s3Client
            ->method('getCommand')
            ->willReturn($command);
        $this->s3Client
            ->expects(self::once())
            ->method('executeAsync')
            ->with($command)
            ->willReturn($s3promise);

        $this->s3mount->deleteAsync(self::UUID, self::A_FILE_NAME);
    }

    public function test_promise_deleteAsync_shouldSetOnFulfilledAndRejectCallbacks(): void
    {
        $s3Promise = $this->createMock(PromiseInterface::class);
        $expectedPromise = $this->createMock(PromiseInterface::class);
        $this->givenCommand();
        $this->s3Client
            ->method('executeAsync')
            ->willReturn($s3Promise);
        $s3Promise
            ->expects(self::once())
            ->method('then')
            ->with(
                self::isInstanceOf(Closure::class)
            )
            ->willReturn($expectedPromise);

        $actualPromise = $this->s3mount->deleteAsync(self::UUID, self::A_FILE_NAME);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_promiseFulfilled_deleteAsync_shouldChainTrueAsValue(): void
    {
        $clientPromise = new Promise();
        $called = false;
        $fulfilledValue = null;
        $this->givenCommand();
        $this->s3Client
            ->method('executeAsync')
            ->willReturn($clientPromise);
        $promise = $this->s3mount->deleteAsync(self::UUID, self::A_FILE_NAME);
        $promise->then(function ($value) use (&$called, &$fulfilledValue) {
            $called = true;
            $fulfilledValue = $value;
        });

        $clientPromise->resolve($this->createMock(Response::class));
        Utils::queue()->run();

        self::assertTrue($called);
        self::assertTrue($fulfilledValue);
    }

    public function test_uuid_putAsync_shouldGetPathOfFile()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID, self::A_FILE_NAME)
            ->willReturn(self::A_PATH);
        $adaptorPromise = $this->createMock(PromiseInterface::class);
        $returnedPromise = $this->createMock(PromiseInterface::class);
        $this->asyncAdapter
            ->method('upload')
            ->willReturn($adaptorPromise);
        $adaptorPromise
            ->method('then')
            ->willReturn($returnedPromise);

        $this->s3mount->putAsync(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);
    }

    public function test_path_putAsync_shouldUploadPathAndContents(): void
    {
        $adaptorPromise = $this->createMock(PromiseInterface::class);
        $returnedPromise = $this->createMock(PromiseInterface::class);
        $this->givenGeneratedPath();
        $this->asyncAdapter
            ->expects(self::once())
            ->method('upload')
            ->with(self::A_PATH, self::A_FILE_CONTENT)
            ->willReturn($adaptorPromise);
        $adaptorPromise
            ->method('then')
            ->willReturn($returnedPromise);

        $this->s3mount->putAsync(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);
    }

    public function test_promise_putAsync_shouldAddFulfilledCallbackAndReturnPromise(): void
    {
        $this->givenGeneratedPath();
        $adaptorPromise = $this->createMock(PromiseInterface::class);
        $expectedPromise = $this->createMock(PromiseInterface::class);
        $this->asyncAdapter
            ->method('upload')
            ->willReturn($adaptorPromise);
        $adaptorPromise
            ->expects(self::once())
            ->method('then')
            ->with(
                self::isInstanceOf(Closure::class)
            )
            ->willReturn($expectedPromise);

        $actualPromise = $this->s3mount->putAsync(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_adapterPromiseFulfilled_putAsync_returnPromisedValueShouldBeTrue(): void
    {
        $this->givenGeneratedPath();
        $adaptorPromise = new Promise();
        $this->asyncAdapter
            ->method('upload')
            ->willReturn($adaptorPromise);
        $called = false;
        $fulfilledValue = null;
        $promise = $this->s3mount->putAsync(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);
        $promise->then(function ($value) use (&$called, &$fulfilledValue) {
            $called = true;
            $fulfilledValue = $value;
        });

        $adaptorPromise->resolve($this->createMock(Response::class));
        Utils::queue()->run();

        self::assertTrue($called);
        self::assertTrue($fulfilledValue);
    }

    public function test_uuid_putStreamAsync_shouldGetPathOfFile()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID, self::A_FILE_NAME)
            ->willReturn(self::A_PATH);
        $adaptorPromise = $this->createMock(PromiseInterface::class);
        $returnedPromise = $this->createMock(PromiseInterface::class);
        $this->asyncAdapter
            ->method('upload')
            ->willReturn($adaptorPromise);
        $adaptorPromise
            ->method('then')
            ->willReturn($returnedPromise);

        $this->s3mount->putStreamAsync(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);
    }

    public function test_path_putStreamAsync_shouldUploadPathAndContents(): void
    {
        $adaptorPromise = $this->createMock(PromiseInterface::class);
        $returnedPromise = $this->createMock(PromiseInterface::class);
        $this->givenGeneratedPath();
        $this->asyncAdapter
            ->expects(self::once())
            ->method('upload')
            ->with(self::A_PATH, self::A_FILE_CONTENT)
            ->willReturn($adaptorPromise);
        $adaptorPromise
            ->method('then')
            ->willReturn($returnedPromise);

        $this->s3mount->putStreamAsync(self::UUID, self::A_FILE_CONTENT, self::A_FILE_NAME);
    }

    public function test_promise_putStreamAsync_shouldAddFulfilledCallbackAndReturnPromise(): void
    {
        $this->givenGeneratedPath();
        $adaptorPromise = $this->createMock(PromiseInterface::class);
        $expectedPromise = $this->createMock(PromiseInterface::class);
        $this->asyncAdapter
            ->method('upload')
            ->willReturn($adaptorPromise);
        $adaptorPromise
            ->expects(self::once())
            ->method('then')
            ->with(
                self::isInstanceOf(Closure::class)
            )
            ->willReturn($expectedPromise);

        $actualPromise = $this->s3mount->putStreamAsync(self::UUID, self::A_FILE_CONTENT, self::A_FILE_NAME);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_adapterPromiseFulfilled_putStreamAsync_returnPromisedValueShouldBeTrue(): void
    {
        $this->givenGeneratedPath();
        $adaptorPromise = new Promise();
        $this->asyncAdapter
            ->method('upload')
            ->willReturn($adaptorPromise);
        $called = false;
        $fulfilledValue = null;
        $promise = $this->s3mount->putStreamAsync(self::UUID, self::A_FILE_CONTENT, self::A_FILE_NAME);
        $promise->then(function ($value) use (&$called, &$fulfilledValue) {
            $called = true;
            $fulfilledValue = $value;
        });

        $adaptorPromise->resolve($this->createMock(Response::class));
        Utils::queue()->run();

        self::assertTrue($called);
        self::assertTrue($fulfilledValue);
    }

    public function test_uuid_getMetadata_shouldGetPathOfFile()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->s3mount->getMetadata(self::UUID, self::A_FILE_NAME);
    }

    public function test_path_getMetadata_shouldGetMetadata()
    {
        $this->givenGeneratedPath();

        $this->fileSystem
            ->expects(self::once())
            ->method('getMetadata')
            ->with(self::A_PATH);

        $this->s3mount->getMetadata(self::UUID, self::A_FILE_NAME);
    }

    public function test_Path_getMetadata_shouldReturnMetadata()
    {

        $this->fileSystem->method('getMetadata')->willReturn(['timestamp' => 0, 'mimetype' => 0]);

        $metadata = $this->s3mount->getMetadata(self::UUID, self::A_FILE_NAME);

        self::assertInstanceOf(Metadata::class, $metadata);
    }

    public function test_FileNotWritten_getMetadata_shouldReturnFalse()
    {

        $this->fileSystem->method('getMetadata')->willReturn(false);

        $metadata = $this->s3mount->getMetadata(self::UUID, self::A_FILE_NAME);

        $this->assertFalse($metadata);
    }


    public function test_retreive_shouldGetS3Client()
    {
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));

        $this->s3Adapter
            ->expects(self::once())
            ->method('getClient');

        $this->s3mount->retrieve(self::UUID, self::A_FILE_NAME);
    }

    public function test_uuid_retreive_shouldGetPathOfFile()
    {
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));

        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->s3mount->retrieve(self::UUID, self::A_FILE_NAME);
    }

    public function test_path_retreive_shouldGetFileLocation()
    {
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));
        $this->givenGeneratedPath();

        $this->s3Adapter
            ->expects(self::once())
            ->method('applyPathPrefix')
            ->with(self::A_PATH);

        $this->s3mount->retrieve(self::UUID, self::A_FILE_NAME);
    }

    public function test_location_retreive_shouldRestoreObject()
    {
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Adapter->method('applyPathPrefix')->willReturn(self::A_LOCATION);
        $this->s3Adapter->method('getBucket')->willReturn(self::S3_BUCKET);

        $this->s3Client
            ->expects(self::once())
            ->method('getCommand')
            ->with(
                'restoreObject',
                [
                    'Bucket' => self::S3_BUCKET,
                    'Key' => self::A_LOCATION,
                    'RestoreRequest' => [
                        'Days' => S3::RESTORED_OBJECT_LIFE_TIME_IN_DAYS,
                        'GlacierJobParameters' => [
                            'Tier' => 'Standard'
                        ]
                    ]
                ]
            )
            ->willReturn($this->createMock(CommandInterface::class));

        $this->s3mount->retrieve(self::UUID, self::A_FILE_NAME);
    }

    public function test_command_retreive_shouldExecuteCommand()
    {
        $command = $this->createMock(CommandInterface::class);
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($command);

        $this->s3Client
            ->expects(self::once())
            ->method('execute')
            ->with($command);

        $this->s3mount->retrieve(self::UUID, self::A_FILE_NAME);
    }

    public function test_commandFailed_retreive_shouldCatchAndThrowNewException()
    {
        $command = $this->createMock(CommandInterface::class);
        $this->s3Adapter->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($command);
        $this->s3Client->method('execute')->willThrowException($this->createMock(S3Exception::class));

        self::expectException(CantRetreiveFileException::class);

        $this->s3mount->retrieve(self::UUID, self::A_FILE_NAME);
    }

    public function test_copy_shouldGeneratePathForSourceAndTargetUuid()
    {
        $this->pathGenerator
            ->expects(self::exactly(2))
            ->method('generatePath')
            ->with(
                ...self::withConsecutive(
                    [self::SOURCE_UUID, self::A_FILE_NAME],
                    [self::TARGET_UUID, self::A_FILE_NAME]
                )
            );

        $this->s3mount->copy(self::SOURCE_UUID, self::TARGET_UUID, self::A_FILE_NAME);
    }

    public function test_Paths_copy_shouldCopySourcePathToTargetPathAndReturnResult()
    {
        $this->pathGenerator->method('generatePath')->willReturnOnConsecutiveCalls(self::SOURCE_PATH,
            self::TARGET_PATH);
        $this->fileSystem
            ->expects(self::once())
            ->method('copy')
            ->with(self::SOURCE_PATH, self::TARGET_PATH)
            ->willReturn(self::COPY_RESULT);

        $actualResult = $this->s3mount->copy(self::SOURCE_UUID, self::TARGET_UUID, self::A_FILE_NAME);

        $this->assertEquals(self::COPY_RESULT, $actualResult);
    }

    public function test_copyAsync_shouldGeneratePaths(): void
    {
        $this->pathGenerator
            ->expects(self::exactly(2))
            ->method('generatePath')
            ->with(
                ...self::withConsecutive(
                    [self::SOURCE_UUID, self::A_FILE_NAME],
                    [self::TARGET_UUID, self::A_FILE_NAME]
                )
            )
            ->willReturn(self::A_PATH);
        $adaptorPromise = $this->createMock(PromiseInterface::class);
        $expectedPromise = $this->createMock(PromiseInterface::class);
        $this->asyncAdapter
            ->method('copy')
            ->willReturn($adaptorPromise);
        $adaptorPromise
            ->method('then')
            ->willReturn($expectedPromise);

        $this->s3mount->copyAsync(self::SOURCE_UUID, self::TARGET_UUID, self::A_FILE_NAME);
    }

    public function test_paths_copyAsync_shouldCopyAsync(): void
    {
        $this->pathGenerator
            ->method('generatePath')
            ->willReturnMap([
                [self::SOURCE_UUID, self::A_FILE_NAME, self::SOURCE_PATH],
                [self::TARGET_UUID, self::A_FILE_NAME, self::TARGET_PATH]
            ]);
        $adaptorPromise = $this->createMock(PromiseInterface::class);
        $expectedPromise = $this->createMock(PromiseInterface::class);
        $this->asyncAdapter
            ->expects(self::once())
            ->method('copy')
            ->with(self::SOURCE_PATH, self::TARGET_PATH)
            ->willReturn($adaptorPromise);
        $adaptorPromise
            ->method('then')
            ->willReturn($expectedPromise);

        $this->s3mount->copyAsync(self::SOURCE_UUID, self::TARGET_UUID, self::A_FILE_NAME);
    }

    public function test_promise_copyAsync_shouldAddFulfilledCallbackAndReturnPromise(): void
    {
        $this->givenGeneratedPath();
        $adaptorPromise = $this->createMock(PromiseInterface::class);
        $expectedPromise = $this->createMock(PromiseInterface::class);
        $this->asyncAdapter
            ->method('copy')
            ->willReturn($adaptorPromise);
        $adaptorPromise
            ->expects(self::once())
            ->method('then')
            ->with(
                self::isInstanceOf(Closure::class)
            )
            ->willReturn($expectedPromise);

        $actualPromise = $this->s3mount->copyAsync(self::SOURCE_UUID, self::TARGET_UUID, self::A_FILE_NAME);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_adapterPromiseFulfilled_copyAsync_returnPromisedValueShouldBeTrue(): void
    {
        $this->givenGeneratedPath();
        $adaptorPromise = new Promise();
        $this->asyncAdapter
            ->method('copy')
            ->willReturn($adaptorPromise);
        $called = false;
        $fulfilledValue = null;
        $promise = $this->s3mount->copyAsync(self::SOURCE_UUID, self::TARGET_UUID, self::A_FILE_NAME);
        $promise->then(function ($value) use (&$called, &$fulfilledValue) {
            $called = true;
            $fulfilledValue = $value;
        });

        $adaptorPromise->resolve($this->createMock(Response::class));
        Utils::queue()->run();

        self::assertTrue($called);
        self::assertTrue($fulfilledValue);
    }

    public function test_UuidAndName_has_shouldGeneratePath()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID, self::A_FILE_NAME);

        $this->s3mount->has(self::UUID, self::A_FILE_NAME);
    }

    public function test_Path_has_shouldGetAndReturnMountHasResult()
    {
        $this->pathGenerator->method('generatePath')->willReturn(self::A_FILE_PATH);
        $this->fileSystem
            ->expects(self::once())
            ->method('has')
            ->with(self::A_FILE_PATH)
            ->willReturn(self::HAS_RESULT);

        $actualResult = $this->s3mount->has(self::UUID, self::A_FILE_NAME);

        $this->assertSame(self::HAS_RESULT, $actualResult);
    }

    public function test_uuidAndName_hasAsync_shouldGeneratePath()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID, self::A_FILE_NAME)
            ->willReturn(self::A_FILE_PATH);

        $this->s3mount->hasAsync(self::UUID, self::A_FILE_NAME);
    }

    public function test_path_hasAsync_shouldGetAndReturnPromiseWithHasResult()
    {
        $expectedPromise = $this->createMock(FulfilledPromise::class);
        $this->pathGenerator
            ->method('generatePath')
            ->willReturn(self::A_FILE_PATH);
        $this->asyncAdapter
            ->expects(self::once())
            ->method('has')
            ->with(self::A_FILE_PATH)
            ->willReturn($expectedPromise);

        $actualPromise = $this->s3mount->hasAsync(self::UUID, self::A_FILE_NAME);

        $this->assertSame($expectedPromise, $actualPromise);
    }

    protected function givenS3CommandAndPromise(): void
    {
        $promise = new Promise();
        $command = $this->createMock(CommandInterface::class);
        $this->s3Client
            ->method('getCommand')
            ->willReturn($command);
        $this->s3Client
            ->method('executeAsync')
            ->willReturn($promise);
    }

    protected function givenCommand(): void
    {
        $this->s3Client
            ->method('getCommand')
            ->willReturn($this->createMock(CommandInterface::class));
    }

    protected function givenGeneratedPath(): void
    {
        $this->pathGenerator
            ->method('generatePath')
            ->willReturn(self::A_PATH);
    }
}

class s3MountTestable extends S3
{
    protected function getFileContent($path): string
    {
        return S3Test::A_FILE_CONTENT;
    }
}
