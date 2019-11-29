<?php

namespace Kronos\Tests\FileSystem\Mount\S3;

use Aws\CommandInterface;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Kronos\FileSystem\Exception\CantRetreiveFileException;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Internal\Metadata;
use Kronos\FileSystem\Mount\PathGeneratorInterface;
use Kronos\FileSystem\Mount\S3\S3;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class S3Test extends TestCase
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

    /**
     * @var PathGeneratorInterface|MockObject
     */
    private $pathGenerator;

    /**
     * @var AwsS3Adapter|MockObject
     */
    private $s3Adaptor;

    /**
     * @var FileSystem|MockObject
     */
    private $fileSystem;

    /**
     * @var S3Client|MockObject
     */
    private $s3Client;

    /**
     * @var S3
     */
    private $s3mount;

    public function setUp(): void
    {
        $this->fileSystem = $this->createMock(Filesystem::class);
        $this->s3Client = $this->createMock(S3Client::class);
        $this->s3Adaptor = $this->createMock(AwsS3Adapter::class);
        $this->pathGenerator = $this->createMock(PathGeneratorInterface::class);

        $this->fileSystem->method('getAdapter')->willReturn($this->s3Adaptor);

        $this->s3mount = new s3MountTestable($this->pathGenerator, $this->fileSystem);
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
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

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
        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));
        $this->s3Client->method('createPresignedRequest')->willReturn($this->createMock(RequestInterface::class));

        $this->s3Adaptor
            ->expects(self::once())
            ->method('getClient');

        $this->s3mount->getUrl(self::UUID, self::A_FILE_NAME);
    }

    public function test_uuid_getSignedUrl_shouldGetPathOfFile()
    {
        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
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
        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));
        $this->s3Client->method('createPresignedRequest')->willReturn($this->createMock(RequestInterface::class));
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

        $this->s3Adaptor
            ->expects(self::once())
            ->method('applyPathPrefix')
            ->with(self::A_PATH);

        $this->s3mount->getUrl(self::UUID, self::A_FILE_NAME);
    }

    public function test_location_getSignedUrl_shouldGetCommandToGetFile()
    {
        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
        $this->s3Adaptor->method('applyPathPrefix')->willReturn(self::A_LOCATION);
        $this->s3Adaptor->method('getBucket')->willReturn(self::S3_BUCKET);
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
        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
        $this->s3Adaptor->method('applyPathPrefix')->willReturn(self::A_LOCATION);
        $this->s3Adaptor->method('getBucket')->willReturn(self::S3_BUCKET);
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

        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
        $this->s3Adaptor->method('applyPathPrefix')->willReturn(self::A_LOCATION);
        $this->s3Adaptor->method('getBucket')->willReturn(self::S3_BUCKET);
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
        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
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
        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));
        $this->s3Client->method('createPresignedRequest')->willReturn($request);

        $request
            ->expects(self::once())
            ->method('getUri');

        $this->s3mount->getUrl(self::UUID, self::A_FILE_NAME);
    }

    public function test_uri_getSignedUrl_shouldReturnUri()
    {
        $request = $this->createMock(RequestInterface::class);
        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));
        $this->s3Client->method('createPresignedRequest')->willReturn($request);
        $request->method('getUri')->willReturn(self::AN_URI);

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

    public function test_path_put_shouldPut()
    {
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

        $this->fileSystem
            ->expects(self::once())
            ->method('put')
            ->with(self::A_PATH, self::A_FILE_CONTENT);

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

    public function test_path_putStream_shouldPutAndReturnResult()
    {
        $stream = tmpfile();
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);
        $this->fileSystem
            ->expects(self::once())
            ->method('putStream')
            ->with(self::A_PATH, $stream)
            ->willReturn(self::PUT_RESULT);

        $actualResult = $this->s3mount->putStream(self::UUID, $stream, self::A_FILE_NAME);

        $this->assertSame(self::PUT_RESULT, $actualResult);
    }

    public function test_getMountType_ShouldReutrnMountType()
    {
        $actualMountType = $this->s3mount->getMountType();

        self::assertEquals(S3::MOUNT_TYPE, $actualMountType);
    }

    public function test_uuid_delete_shouldGetPathOfFile()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->s3mount->delete(self::UUID, self::A_FILE_NAME);
    }

    public function test_path_delete_shouldDeleteFile()
    {
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

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
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

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
        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));

        $this->s3Adaptor
            ->expects(self::once())
            ->method('getClient');

        $this->s3mount->retrieve(self::UUID, self::A_FILE_NAME);
    }

    public function test_uuid_retreive_shouldGetPathOfFile()
    {
        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));

        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->s3mount->retrieve(self::UUID, self::A_FILE_NAME);
    }

    public function test_path_retreive_shouldGetFileLocation()
    {
        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($this->createMock(CommandInterface::class));
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

        $this->s3Adaptor
            ->expects(self::once())
            ->method('applyPathPrefix')
            ->with(self::A_PATH);

        $this->s3mount->retrieve(self::UUID, self::A_FILE_NAME);
    }

    public function test_location_retreive_shouldRestoreObject()
    {
        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
        $this->s3Adaptor->method('applyPathPrefix')->willReturn(self::A_LOCATION);
        $this->s3Adaptor->method('getBucket')->willReturn(self::S3_BUCKET);

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
        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
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
        $this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
        $this->s3Client->method('getCommand')->willReturn($command);
        $this->s3Client->method('execute')->willThrowException($this->createMock(S3Exception::class));

        self::expectException(CantRetreiveFileException::class);

        $this->s3mount->retrieve(self::UUID, self::A_FILE_NAME);
    }

    public function test_copy_shouldGeneratePathForSourceUuid()
    {
        $this->pathGenerator
            ->expects(self::at(0))
            ->method('generatePath')
            ->with(self::SOURCE_UUID, self::A_FILE_NAME);

        $this->s3mount->copy(self::SOURCE_UUID, self::TARGET_UUID, self::A_FILE_NAME);
    }

    public function test_SourcePath_copy_shouldGeneratePathForTargetUuid()
    {
        $this->pathGenerator
            ->expects(self::at(1))
            ->method('generatePath')
            ->with(self::TARGET_UUID, self::A_FILE_NAME);

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
}

class s3MountTestable extends \Kronos\FileSystem\Mount\S3\S3
{

    protected function getFileContent($path)
    {
        return S3Test::A_FILE_CONTENT;
    }

}
