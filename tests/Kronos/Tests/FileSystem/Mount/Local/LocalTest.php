<?php

namespace Kronos\Tests\FileSystem\Mount\Local;

use Exception;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Kronos\FileSystem\Exception\WrongTypeException;
use Kronos\FileSystem\File\Internal\Metadata;
use Kronos\FileSystem\PromiseFactory;
use Kronos\FileSystem\Mount\PathGeneratorInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\File;
use League\Flysystem\Directory;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocalTest extends TestCase
{

    const UUID = 'UUID';
    const A_PATH = 'A_PATH';
    const A_FILE_NAME = 'A_FILE_NAME';
    const A_FILE_PATH = 'A_FILE_PATH';
    const A_LOCATION = 'A_LOCATION';
    const AN_URI = 'AN_URI';
    const BASE_URL = '/base/url?id=';
    const A_FILE_CONTENT = 'File contents';
    const PUT_RESULT = self::COPY_RESULT;
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
     * @var Local|MockObject
     */
    private $localAdaptor;

    /**
     * @var FileSystem|MockObject
     */
    private $fileSystem;

    /**
     * @var PromiseFactory|MockObject
     */
    private $promiseFactory;

    /**
     * @var \Kronos\FileSystem\Mount\Local\Local
     */
    private $localMount;

    const FORCE_DOWNLOAD_PARAMETER = '&force=1';

    public function setUp(): void
    {
        $this->fileSystem = $this->createMock(Filesystem::class);
        $this->localAdaptor = $this->createMock(Local::class);
        $this->pathGenerator = $this->createMock(PathGeneratorInterface::class);
        $this->promiseFactory = $this->createMock(PromiseFactory::class);

        $this->fileSystem->method('getAdapter')->willReturn($this->localAdaptor);

        $this->localMount = new localMountTestable($this->pathGenerator, $this->fileSystem, $this->promiseFactory);
    }

    public function test_uuid_get_shouldGetPathOfFile()
    {
        $this->fileSystem->method('get')->willReturn($this->createMock(File::class));
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->localMount->get(self::UUID, self::A_FILE_NAME);
    }

    public function test_path_get_shouldReadStream()
    {
        $this->fileSystem->method('get')->willReturn($this->createMock(File::class));
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

        $this->fileSystem
            ->expects(self::once())
            ->method('get')
            ->with(self::A_PATH);

        $this->localMount->get(self::UUID, self::A_FILE_NAME);
    }

    public function test_getDirectory_shouldThrowException()
    {
        $this->fileSystem->method('get')->willReturn($this->createMock(Directory::class));
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

        $this->expectException(WrongTypeException::class);

        $this->fileSystem
            ->expects(self::once())
            ->method('get')
            ->with(self::A_PATH);

        $this->localMount->get(self::UUID, self::A_FILE_NAME);
    }

    public function test_stream_get_shouldReturnStream()
    {
        $this->fileSystem->method('get')->willReturn($this->createMock(File::class));

        $file = $this->localMount->get(self::UUID, self::A_FILE_NAME);

        $this->assertInstanceOf(\Kronos\FileSystem\File\File::class, $file);
    }

    public function test_uuid_delete_shouldGetPathOfFile()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->localMount->delete(self::UUID, self::A_FILE_NAME);
    }

    public function test_path_delete_shouldDeleteFile()
    {
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

        $this->fileSystem
            ->expects(self::once())
            ->method('delete')
            ->with(self::A_PATH);

        $this->localMount->delete(self::UUID, self::A_FILE_NAME);
    }

    public function test_FileDeleted_delete_shouldReturnTrue()
    {

        $this->fileSystem->method('delete')->willReturn(self::PUT_RESULT);

        $deleted = $this->localMount->delete(self::UUID, self::A_FILE_NAME);

        $this->assertTrue($deleted);
    }

    public function test_FileNotDeleted_delete_shouldReturnFalse()
    {

        $this->fileSystem->method('delete')->willReturn(false);

        $deleted = $this->localMount->delete(self::UUID, self::A_FILE_NAME);

        $this->assertFalse($deleted);
    }

    public function test_uuid_deleteAsync_shouldGetPathOfFile(): void
    {
        $this->givenFulfilledPromise();
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->localMount->deleteAsync(self::UUID, self::A_FILE_NAME);
    }

    public function test_path_deleteAsync_shouldDeleteFile(): void
    {
        $this->givenFulfilledPromise();
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

        $this->fileSystem
            ->expects(self::once())
            ->method('delete')
            ->with(self::A_PATH);

        $this->localMount->deleteAsync(self::UUID, self::A_FILE_NAME);
    }

    public function test_FileDeleted_deleteAsync_shouldCreateAndReturnPromise(): void
    {
        $expectedPromise = $this->createMock(FulfilledPromise::class);
        $this->fileSystem->method('delete')->willReturn(self::PUT_RESULT);
        $this->promiseFactory
            ->expects(self::once())
            ->method('createFulfilledPromise')
            ->with(self::PUT_RESULT)
            ->willReturn($expectedPromise);

        $actualPromise = $this->localMount->deleteAsync(self::UUID, self::A_FILE_NAME);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_deleteThrowsException_deleteAsync_shouldCreateAndReturnPromise(): void
    {
        $exception = new Exception();
        $expectedPromise = $this->createMock(RejectedPromise::class);
        $this->fileSystem
            ->method('delete')
            ->willThrowException($exception);
        $this->promiseFactory
            ->expects(self::once())
            ->method('createRejectedPromise')
            ->with($exception)
            ->willReturn($expectedPromise);

        $actualPromise = $this->localMount->deleteAsync(self::UUID, self::A_FILE_NAME);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_uuid_put_shouldGetPathOfFile()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->localMount->put(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);
    }

    public function test_path_put_shouldPut()
    {
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

        $this->fileSystem
            ->expects(self::once())
            ->method('put')
            ->with(self::A_PATH, self::A_FILE_CONTENT);

        $this->localMount->put(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);
    }

    public function test_FileWritten_put_shouldReturnTrue()
    {

        $this->fileSystem->method('put')->willReturn(self::PUT_RESULT);

        $written = $this->localMount->put(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);

        $this->assertTrue($written);
    }

    public function test_FileNotWritten_put_shouldReturnFalse()
    {

        $this->fileSystem->method('put')->willReturn(false);

        $written = $this->localMount->put(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);

        $this->assertFalse($written);
    }

    public function test_uuid_putAsync_shouldGetPathOfFile()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->localMount->putAsync(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);
    }

    public function test_path_putAsync_shouldPut()
    {
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

        $this->fileSystem
            ->expects(self::once())
            ->method('put')
            ->with(self::A_PATH, self::A_FILE_CONTENT);

        $this->localMount->putAsync(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);
    }

    public function test_FileWritten_putAsync_shouldCreateAndReturnFulfilledPromise()
    {
        $this->fileSystem
            ->method('put')
            ->willReturn(self::PUT_RESULT);
        $expectedPromise = $this->createMock(FulfilledPromise::class);
        $this->promiseFactory
            ->expects(self::once())
            ->method('createFulfilledPromise')
            ->with(self::PUT_RESULT)
            ->willReturn($expectedPromise);

        $actualPromise = $this->localMount->putAsync(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_putStream_shouldGetPathOfFile()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->localMount->putStream(self::UUID, self::A_FILE_CONTENT, self::A_FILE_NAME);
    }

    public function test_path_putStream_shouldPutAndReturnResult()
    {
        $resource = tmpfile();
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);
        $this->fileSystem
            ->expects(self::once())
            ->method('putStream')
            ->with(self::A_PATH, $resource)
            ->willReturn(self::PUT_RESULT);

        $actualResult = $this->localMount->putStream(self::UUID, $resource, self::A_FILE_NAME);

        $this->assertSame(self::PUT_RESULT, $actualResult);
    }

    public function test_putStreamAsync_shouldGetPathOfFile()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->localMount->putStreamAsync(self::UUID, self::A_FILE_CONTENT, self::A_FILE_NAME);
    }

    public function test_path_putStreamAsync_shouldPutStream()
    {
        $resource = tmpfile();
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);
        $this->fileSystem
            ->expects(self::once())
            ->method('putStream')
            ->with(self::A_PATH, $resource);

        $this->localMount->putStreamAsync(self::UUID, $resource, self::A_FILE_NAME);
    }

    public function test_FileWritten_putStreamAsync_shouldCreateAndReturnFulfilledPromise()
    {
        $this->fileSystem
            ->method('putStream')
            ->willReturn(self::PUT_RESULT);
        $expectedPromise = $this->createMock(FulfilledPromise::class);
        $this->promiseFactory
            ->expects(self::once())
            ->method('createFulfilledPromise')
            ->with(self::PUT_RESULT)
            ->willReturn($expectedPromise);

        $actualPromise = $this->localMount->putStreamAsync(self::UUID, self::A_FILE_PATH, self::A_FILE_NAME);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_getMountType_ShouldReturnMountType()
    {
        $actualMountType = $this->localMount->getMountType();

        self::assertEquals(\Kronos\FileSystem\Mount\Local\Local::MOUNT_TYPE, $actualMountType);
    }

    public function test_uuid_getMetadata_shouldGetPathOfFile()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID);

        $this->localMount->getMetadata(self::UUID, self::A_FILE_NAME);
    }

    public function test_path_getMetadata_shouldDeleteFile()
    {
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

        $this->fileSystem
            ->expects(self::once())
            ->method('getMetadata')
            ->with(self::A_PATH);

        $this->localMount->getMetadata(self::UUID, self::A_FILE_NAME);
    }

    public function test_Path_getMetadata_shouldReturnMetadata()
    {

        $this->fileSystem->method('getMetadata')->willReturn(['timestamp' => 0, 'size' => 0]);

        $metadata = $this->localMount->getMetadata(self::UUID, self::A_FILE_NAME);

        self::assertInstanceOf(Metadata::class, $metadata);
    }

    public function test_Metadata_getMetadata_shouldGetMimeType()
    {
        $this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);
        $this->fileSystem->method('getMetadata')->willReturn(['timestamp' => 0, 'size' => 0]);

        $this->fileSystem
            ->expects(self::once())
            ->method('getMimeType')
            ->with(self::A_PATH);

        $this->localMount->getMetadata(self::UUID, self::A_FILE_NAME);
    }

    public function test_FileNotWritten_getMetadata_shouldReturnFalse()
    {

        $this->fileSystem->method('getMetadata')->willReturn(false);

        $metadata = $this->localMount->getMetadata(self::UUID, self::A_FILE_NAME);

        $this->assertFalse($metadata);
    }

    public function test_BaseUrl_getUrl_shouldReturnUrl()
    {
        $this->localMount->setBaseUrl(self::BASE_URL);

        $actualUrl = $this->localMount->getUrl(self::UUID, self::A_FILE_NAME);

        self::assertEquals(self::BASE_URL . self::UUID, $actualUrl);
    }

    public function test_ForceDownloadAndParameterSet_getUrl_shouldReturnUrlWithParameter()
    {
        $this->localMount->setBaseUrl(self::BASE_URL);
        $this->localMount->setForceDownloadParameter(self::FORCE_DOWNLOAD_PARAMETER);

        $actualUrl = $this->localMount->getUrl(self::UUID, self::A_FILE_NAME, true);

        self::assertEquals(self::BASE_URL . self::UUID . self::FORCE_DOWNLOAD_PARAMETER, $actualUrl);
    }

    public function test_DoNotForceDownloadAndParameterSet_getUrl_shouldOnlyReturnUrl()
    {
        $this->localMount->setBaseUrl(self::BASE_URL);
        $this->localMount->setForceDownloadParameter(self::FORCE_DOWNLOAD_PARAMETER);

        $actualUrl = $this->localMount->getUrl(self::UUID, self::A_FILE_NAME);

        self::assertEquals(self::BASE_URL . self::UUID, $actualUrl);
    }

    public function test_ForceDownloadAndParameterNotSet_getUrl_shouldOnlyReturnUrl()
    {
        $this->localMount->setBaseUrl(self::BASE_URL);

        $actualUrl = $this->localMount->getUrl(self::UUID, self::A_FILE_NAME, true);

        self::assertEquals(self::BASE_URL . self::UUID, $actualUrl);
    }

    public function test_NoBaseUrl_getUrl_shouldThrowException()
    {
        $this->expectException(Exception::class);

        $this->localMount->getUrl(self::UUID, self::A_FILE_NAME);
    }

    public function test_copy_shouldGeneratePathForSourceAndTargetUuid()
    {
        $this->pathGenerator
            ->expects(self::exactly(2))
            ->method('generatePath')
            ->withConsecutive(
                [self::SOURCE_UUID, self::A_FILE_NAME],
                [self::TARGET_UUID, self::A_FILE_NAME]
            );

        $this->localMount->copy(self::SOURCE_UUID, self::TARGET_UUID, self::A_FILE_NAME);
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

        $actualResult = $this->localMount->copy(self::SOURCE_UUID, self::TARGET_UUID, self::A_FILE_NAME);

        $this->assertEquals(self::COPY_RESULT, $actualResult);
    }

    public function test_copyAsync_shouldGeneratePathForSourceAndTargetUuid()
    {
        $this->pathGenerator
            ->expects(self::exactly(2))
            ->method('generatePath')
            ->withConsecutive(
                [self::SOURCE_UUID, self::A_FILE_NAME],
                [self::TARGET_UUID, self::A_FILE_NAME]
            );

        $this->localMount->copyAsync(self::SOURCE_UUID, self::TARGET_UUID, self::A_FILE_NAME);
    }

    public function test_Paths_copyAsync_shouldCopySourcePathToTargetPathAndReturnFulfilledPromiseWithResult()
    {
        $this->pathGenerator->method('generatePath')->willReturnOnConsecutiveCalls(self::SOURCE_PATH,
            self::TARGET_PATH);
        $this->fileSystem
            ->expects(self::once())
            ->method('copy')
            ->with(self::SOURCE_PATH, self::TARGET_PATH)
            ->willReturn(self::COPY_RESULT);
        $expectedPromise = $this->createMock(FulfilledPromise::class);
        $this->promiseFactory
            ->expects(self::once())
            ->method('createFulfilledPromise')
            ->with(self::COPY_RESULT)
            ->willReturn($expectedPromise);

        $actualPromise = $this->localMount->copyAsync(self::SOURCE_UUID, self::TARGET_UUID, self::A_FILE_NAME);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_UuidAndName_has_shouldGeneratePath()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID, self::A_FILE_NAME);

        $this->localMount->has(self::UUID, self::A_FILE_NAME);
    }

    public function test_Path_has_shouldGetAndReturnMountHasResult()
    {
        $this->pathGenerator->method('generatePath')->willReturn(self::A_FILE_PATH);
        $this->fileSystem
            ->expects(self::once())
            ->method('has')
            ->with(self::A_FILE_PATH)
            ->willReturn(self::HAS_RESULT);

        $actualResult = $this->localMount->has(self::UUID, self::A_FILE_NAME);

        $this->assertSame(self::HAS_RESULT, $actualResult);
    }

    public function test_UuidAndName_hasAsync_shouldGeneratePath()
    {
        $this->pathGenerator
            ->expects(self::once())
            ->method('generatePath')
            ->with(self::UUID, self::A_FILE_NAME);

        $this->localMount->hasAsync(self::UUID, self::A_FILE_NAME);
    }

    public function test_path_hasAsync_shouldGetAndReturnPromiseWithHasResult()
    {
        $expectedPromise = $this->createMock(FulfilledPromise::class);
        $this->pathGenerator->method('generatePath')->willReturn(self::A_FILE_PATH);
        $this->fileSystem
            ->expects(self::once())
            ->method('has')
            ->with(self::A_FILE_PATH)
            ->willReturn(self::HAS_RESULT);
        $this->promiseFactory
            ->expects(self::once())
            ->method('createFulfilledPromise')
            ->with(self::HAS_RESULT)
            ->willReturn($expectedPromise);

        $actualPromise = $this->localMount->hasAsync(self::UUID, self::A_FILE_NAME);

        $this->assertSame($expectedPromise, $actualPromise);
    }

    protected function givenFulfilledPromise(): void
    {
        $this->promiseFactory->method('createFulfilledPromise')->willReturn($this->createMock(FulfilledPromise::class));
    }
}


class localMountTestable extends \Kronos\FileSystem\Mount\Local\Local
{

    protected function getFileContent($path)
    {
        return LocalTest::A_FILE_CONTENT;
    }

}
