<?php

namespace Kronos\Tests\FileSystem;

use Closure;
use Exception;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Promise\Utils;
use Kronos\FileSystem\Copy\DestinationChooser;
use Kronos\FileSystem\Copy\DestinationChooserFactory;
use Kronos\FileSystem\Copy\Factory as CopyFactory;
use Kronos\FileSystem\Exception\FileCantBeWrittenException;
use Kronos\FileSystem\Exception\MountNotFoundException;
use Kronos\FileSystem\Exception\WrongTypeException;
use Kronos\FileSystem\ExtensionList;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Internal\Metadata;
use Kronos\FileSystem\File\Translator\MetadataTranslator;
use Kronos\FileSystem\FileRepositoryInterface;
use Kronos\FileSystem\FileSystem;
use Kronos\FileSystem\PromiseFactory;
use Kronos\FileSystem\Mount\MountInterface;
use Kronos\FileSystem\Mount\Selector;
use PHPUnit\Framework\MockObject\MockObject;

class FileSystemTest extends ExtendedTestCase
{
    const A_FILE_PATH = 'A_FILE_PATH';
    const FILE_NAME = 'FILE_NAME';
    const MOUNT_TYPE = 'MOUNT_TYPE';
    const UUID = 'UUID';
    const A_SIGNED_URL = 'A_SIGNED_URL';
    const IMPORTATION_MOUNT_TYPE = 'Importation mount Type';
    const NEW_FILE_UUID = 'newFileUuid';
    const SOURCE_MOUNT_TYPE = 'Source mount type';
    const PUT_STREAM_RESULT = self::HAS_FILE;
    const HAS_FILE = true;
    const REPOSITORY_DELETE_EXCEPTION_MESSAGE = 'failed';
    const DESTINATION_MOUNT_TYPE = 'destination mount type';
    const FILE_STREAM = 'file stream';

    private File|MockObject $file;
    private Metadata|MockObject$metadata;
    private Selector&MockObject $mountSelector;
    private FileRepositoryInterface&MockObject $fileRepository;
    private FileSystem $fileSystem;
    private MountInterface&MockObject $mount;
    private MountInterface&MockObject  $sourceMount;
    private MountInterface&MockObject  $importationMount;
    private MetadataTranslator&MockObject $metadataTranslator;
    private PromiseFactory&MockObject $promiseFactory;
    private CopyFactory&MockObject $copyFactory;
    private DestinationChooserFactory&MockObject $destinationChooserFactory;
    private DestinationChooser&MockObject $destinationChooser;

    public function setUp(): void
    {
        $this->mount = $this->createMock(MountInterface::class);

        $this->metadataTranslator = $this->createMock(MetadataTranslator::class);
        $this->mountSelector = $this->createMock(Selector::class);
        $this->fileRepository = $this->createMock(FileRepositoryInterface::class);
        $this->promiseFactory = $this->createMock(PromiseFactory::class);
        $this->destinationChooserFactory = $this->createMock(DestinationChooserFactory::class);
        $this->copyFactory = $this->createMock(CopyFactory::class);
        $this->copyFactory
            ->method('createDestinationChooserFactory')
            ->willReturn($this->destinationChooserFactory);

        $this->fileSystem = new FileSystem(
            $this->mountSelector,
            $this->fileRepository,
            $this->metadataTranslator,
            $this->promiseFactory,
            $this->copyFactory
        );
    }

    public function tearDown(): void
    {
        unset($this->metadata);
        unset($this->file);
    }

    public function test_constructor_shouldCreateDestinationChooserFactory(): void
    {
        $this->copyFactory
            ->expects(self::once())
            ->method('createDestinationChooserFactory')
            ->with(
                $this->fileRepository,
                $this->mountSelector
            );

        $this->fileSystem = new FileSystem(
            $this->mountSelector,
            $this->fileRepository,
            $this->metadataTranslator,
            $this->promiseFactory,
            $this->copyFactory
        );
    }

    public function test_resource_put_shouldGetImportationMount()
    {
        $this->mount->method('put')->willReturn(self::PUT_STREAM_RESULT);

        $this->mountSelector
            ->expects(self::once())
            ->method('getImportationMount')
            ->willReturn($this->mount);

        $this->fileSystem->put(self::A_FILE_PATH, self::FILE_NAME);
    }

    public function test_mount_put_shouldAddNewFile()
    {
        $this->mount->method('put')->willReturn(self::PUT_STREAM_RESULT);
        $this->mount->method('getMountType')->willReturn(self::MOUNT_TYPE);
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);

        $this->fileRepository
            ->expects(self::once())
            ->method('addNewFile')
            ->with(self::MOUNT_TYPE, self::FILE_NAME);

        $this->fileSystem->put(self::A_FILE_PATH, self::FILE_NAME);
    }

    public function test_mountAndUuid_put_putFile()
    {
        $this->mount->method('put')->willReturn(self::PUT_STREAM_RESULT);
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->fileRepository->method('addNewFile')->willReturn(self::UUID);

        $this->mount
            ->expects(self::once())
            ->method('put')
            ->with(self::UUID, self::A_FILE_PATH, self::FILE_NAME);

        $this->fileSystem->put(self::A_FILE_PATH, self::FILE_NAME);
    }

    public function test_fileAsBeenWritten_put_shouldReturnFileUuid()
    {
        $this->mount->method('put')->willReturn(self::PUT_STREAM_RESULT);
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->fileRepository->method('addNewFile')->willReturn(self::UUID);

        $actualFileUuid = $this->fileSystem->put(self::A_FILE_PATH, self::FILE_NAME);

        self::assertSame(self::UUID, $actualFileUuid);
    }

    public function test_putHaveNotBeenSuccessful_put_shouldThrowException()
    {
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->mount->method('put')->willReturn(false);
        $this->fileRepository->method('addNewFile')->willReturn(self::UUID);

        $this->expectException(FileCantBeWrittenException::class);

        $this->fileSystem->put(self::A_FILE_PATH, self::FILE_NAME);
    }

    public function test_putHaveNotBeenSuccessful_put_shouldDeleteNewUuid()
    {
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->mount->method('put')->willReturn(false);
        $this->fileRepository->method('addNewFile')->willReturn(self::UUID);

        $this->expectException(FileCantBeWrittenException::class);

        $this->fileRepository
            ->expects(self::once())
            ->method('delete')
            ->with(self::UUID);

        $this->fileSystem->put(self::A_FILE_PATH, self::FILE_NAME);
    }

    public function test_resource_putAsync_shouldGetImportationMount()
    {
        $this->mountSelector
            ->expects(self::once())
            ->method('getImportationMount')
            ->willReturn($this->mount);
        $mountPromise = $this->buildMountPutAsyncPromiseChain();
        $this->mount
            ->method('putAsync')
            ->willReturn($mountPromise);

        $this->fileSystem->putAsync(self::A_FILE_PATH, self::FILE_NAME);
    }

    public function test_mount_putAsync_shouldAddNewFile()
    {
        $this->mount->method('getMountType')->willReturn(self::MOUNT_TYPE);
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $mountPromise = $this->buildMountPutAsyncPromiseChain();
        $this->mount
            ->method('putAsync')
            ->willReturn($mountPromise);

        $this->fileRepository
            ->expects(self::once())
            ->method('addNewFile')
            ->with(self::MOUNT_TYPE, self::FILE_NAME);

        $this->fileSystem->putAsync(self::A_FILE_PATH, self::FILE_NAME);
    }

    public function test_mountAndUuid_putAsync_putFileAsync()
    {
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->fileRepository->method('addNewFile')->willReturn(self::UUID);
        $mountPromise = $this->buildMountPutAsyncPromiseChain();

        $this->mount
            ->expects(self::once())
            ->method('putAsync')
            ->with(self::UUID, self::A_FILE_PATH, self::FILE_NAME)
            ->willReturn($mountPromise);

        $this->fileSystem->putAsync(self::A_FILE_PATH, self::FILE_NAME);
    }

    public function test_fileAsBeenWritten_putAsync_shouldReturnFileUuid()
    {
        $mountPromise = $this->createMock(PromiseInterface::class);
        $expectedPromise = $this->createMock(PromiseInterface::class);
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->mount
            ->method('putAsync')
            ->willReturn($mountPromise);

        $mountPromise
            ->expects(self::once())
            ->method('then')
            ->with(
                self::isInstanceOf(Closure::class),
                self::isInstanceOf(Closure::class)
            )
            ->willReturn($expectedPromise);

        $actualPromise = $this->fileSystem->putAsync(self::A_FILE_PATH, self::FILE_NAME);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_mountPromiseFulfilled_putAsync_chainedPromiseShouldResolveToFileUUID()
    {
        $mountPromise = new FulfilledPromise(true);
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->fileRepository->method('addNewFile')->willReturn(self::UUID);
        $this->mount
            ->method('putAsync')
            ->willReturn($mountPromise);
        $called = false;
        $calledWith = null;

        $actualPromise = $this->fileSystem->putAsync(self::A_FILE_PATH, self::FILE_NAME);
        $actualPromise->then(static function($value) use (&$called, &$calledWith) {
            $called = true;
            $calledWith = $value;
        });
        Utils::queue()->run();

        self::assertTrue($called);
        self::assertEquals(self::UUID, $calledWith);
    }

    public function test_mountPromiseRejected_putAsync_shouldDeleteNewFileAndThrowException()
    {
        $mountPromise = new RejectedPromise('rejected');
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->fileRepository->method('addNewFile')->willReturn(self::UUID);
        $this->mount
            ->method('putAsync')
            ->willReturn($mountPromise);

        $this->fileRepository
            ->expects(self::once())
            ->method('delete')
            ->with(self::UUID);
        $this->expectException(FileCantBeWrittenException::class);

        $actualPromise = $this->fileSystem->putAsync(self::A_FILE_PATH, self::FILE_NAME);
        $actualPromise->wait();
    }

    public function test_resource_putStream_shouldGetImportationMount()
    {
        $stream = tmpfile();
        $this->mount->method('putStream')->willReturn(self::PUT_STREAM_RESULT);

        $this->mountSelector
            ->expects(self::once())
            ->method('getImportationMount')
            ->willReturn($this->mount);

        $this->fileSystem->putStream($stream, self::FILE_NAME);
    }

    public function test_mount_putStream_shouldAddNewFile()
    {
        $stream = tmpfile();
        $this->mount->method('putStream')->willReturn(self::PUT_STREAM_RESULT);
        $this->mount->method('getMountType')->willReturn(self::MOUNT_TYPE);
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);

        $this->fileRepository
            ->expects(self::once())
            ->method('addNewFile')
            ->with(self::MOUNT_TYPE, self::FILE_NAME);

        $this->fileSystem->putStream($stream, self::FILE_NAME);
    }

    public function test_mountAndUuid_putStream_putFile()
    {
        $stream = tmpfile();
        $this->mount->method('putStream')->willReturn(self::PUT_STREAM_RESULT);
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->fileRepository->method('addNewFile')->willReturn(self::UUID);

        $this->mount
            ->expects(self::once())
            ->method('putStream')
            ->with(self::UUID, $stream, self::FILE_NAME);

        $this->fileSystem->putStream($stream, self::FILE_NAME);
    }

    public function test_fileAsBeenWritten_putStream_shouldReturnFileUuid()
    {
        $stream = tmpfile();
        $this->mount->method('putStream')->willReturn(self::PUT_STREAM_RESULT);
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->fileRepository->method('addNewFile')->willReturn(self::UUID);

        $actualFileUuid = $this->fileSystem->putStream($stream, self::FILE_NAME);

        self::assertSame(self::UUID, $actualFileUuid);
    }

    public function test_putStreamHaveNotBeenSucessful_put_shouldThrowException()
    {
        $stream = tmpfile();
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->mount->method('putStream')->willReturn(false);
        $this->fileRepository->method('addNewFile')->willReturn(self::UUID);

        $this->expectException(FileCantBeWrittenException::class);

        $this->fileSystem->putStream($stream, self::FILE_NAME);
    }

    public function test_putStreamHaveNotBeenSuccessful_put_shouldDeleteNewUuid()
    {
        $stream = tmpfile();
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->mount->method('putStream')->willReturn(false);
        $this->fileRepository->method('addNewFile')->willReturn(self::UUID);

        $this->expectException(FileCantBeWrittenException::class);

        $this->fileRepository
            ->expects(self::once())
            ->method('delete')
            ->with(self::UUID);

        $this->fileSystem->putStream($stream, self::FILE_NAME);
    }

    public function test_resource_putStreamAsync_shouldGetImportationMount()
    {
        $mountPromise = $this->buildMountPutStreamAsyncPromiseChain();
        $this->mount
            ->method('putStreamAsync')
            ->willReturn($mountPromise);

        $this->mountSelector
            ->expects(self::once())
            ->method('getImportationMount')
            ->willReturn($this->mount);

        $this->fileSystem->putStreamAsync(self::A_FILE_PATH, self::FILE_NAME);
    }

    public function test_mount_putStreamAsync_shouldAddNewFile()
    {
        $this->mount->method('getMountType')->willReturn(self::MOUNT_TYPE);
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $mountPromise = $this->buildMountPutStreamAsyncPromiseChain();
        $this->mount
            ->method('putStreamAsync')
            ->willReturn($mountPromise);

        $this->fileRepository
            ->expects(self::once())
            ->method('addNewFile')
            ->with(self::MOUNT_TYPE, self::FILE_NAME);

        $this->fileSystem->putStreamAsync(self::A_FILE_PATH, self::FILE_NAME);
    }

    public function test_mountAndUuid_putStreamAsync_putFileAsync()
    {
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->fileRepository->method('addNewFile')->willReturn(self::UUID);
        $mountPromise = $this->buildMountPutStreamAsyncPromiseChain();

        $this->mount
            ->expects(self::once())
            ->method('putStreamAsync')
            ->with(self::UUID, self::A_FILE_PATH, self::FILE_NAME)
            ->willReturn($mountPromise);

        $this->fileSystem->putStreamAsync(self::A_FILE_PATH, self::FILE_NAME);
    }

    public function test_fileAsBeenWritten_putStreamAsync_shouldReturnFileUuid()
    {
        $mountPromise = $this->createMock(PromiseInterface::class);
        $expectedPromise = $this->createMock(PromiseInterface::class);
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->mount
            ->method('putStreamAsync')
            ->willReturn($mountPromise);

        $mountPromise
            ->expects(self::once())
            ->method('then')
            ->with(
                self::isInstanceOf(Closure::class),
                self::isInstanceOf(Closure::class)
            )
            ->willReturn($expectedPromise);

        $actualPromise = $this->fileSystem->putStreamAsync(self::A_FILE_PATH, self::FILE_NAME);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_mountPromiseFulfilled_putStreamAsync_chainedPromiseShouldResolveToFileUUID()
    {
        $mountPromise = new FulfilledPromise(true);
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->fileRepository->method('addNewFile')->willReturn(self::UUID);
        $this->mount
            ->method('putStreamAsync')
            ->willReturn($mountPromise);
        $called = false;
        $calledWith = null;

        $actualPromise = $this->fileSystem->putStreamAsync(self::A_FILE_PATH, self::FILE_NAME);
        $actualPromise->then(static function($value) use (&$called, &$calledWith) {
            $called = true;
            $calledWith = $value;
        });
        Utils::queue()->run();

        self::assertTrue($called);
        self::assertEquals(self::UUID, $calledWith);
    }

    public function test_mountPromiseRejected_putStreamAsync_shouldDeleteNewFileAndThrowException()
    {
        $mountPromise = new RejectedPromise('rejected');
        $this->mountSelector->method('getImportationMount')->willReturn($this->mount);
        $this->fileRepository->method('addNewFile')->willReturn(self::UUID);
        $this->mount
            ->method('putStreamAsync')
            ->willReturn($mountPromise);

        $this->fileRepository
            ->expects(self::once())
            ->method('delete')
            ->with(self::UUID);
        $this->expectException(FileCantBeWrittenException::class);

        $actualPromise = $this->fileSystem->putStreamAsync(self::A_FILE_PATH, self::FILE_NAME);
        $actualPromise->wait();
    }

    public function test_givenId_getUrl_shouldMountAssociatedWithId()
    {
        $this->givenMountSelected();

        $this->fileRepository
            ->expects(self::once())
            ->method('getFileMountType')
            ->with(self::UUID);

        $this->fileSystem->getUrl(self::UUID);
    }

    public function test_mountType_getUrl_shouldSelectMount()
    {
        $this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

        $this->mountSelector
            ->expects(self::once())
            ->method('selectMount')
            ->with(self::MOUNT_TYPE)
            ->willReturn($this->mount);

        $this->fileSystem->getUrl(self::UUID);
    }

    public function test_mountSelected_getUrl_shouldGetFilename()
    {
        $this->givenMountSelected();
        $this->fileRepository
            ->expects(self::once())
            ->method('getFileName')
            ->with(self::UUID);

        $this->fileSystem->getUrl(self::UUID);
    }

    public function test_fileName_getUrl_shouldGetUrl()
    {
        $this->givenMountSelected();
        $this->givenFileName();
        $this->mount
            ->expects(self::once())
            ->method('getUrl')
            ->with(self::UUID, self::FILE_NAME, false);

        $this->fileSystem->getUrl(self::UUID);
    }

    public function test_mountCouldNotHaveBeenSelected_getUrl_shouldThrowMountNotFoundException()
    {
        $this->mountSelector->method('selectMount')->willReturn(null);

        $this->expectException(MountNotFoundException::class);

        $this->fileSystem->getUrl(self::UUID);
    }

    public function test_signedUlr_getUrl_shouldReturnSignedUlr()
    {
        $this->givenMountSelected();

        $this->mount->method('getUrl')->willReturn(self::A_SIGNED_URL);

        $actualSignedUrl = $this->fileSystem->getUrl(self::UUID);

        self::assertSame(self::A_SIGNED_URL, $actualSignedUrl);
    }

    public function test_ForceDownloadList_getUrl_shouldCheckIfInList()
    {
        $this->givenMountSelected();
        $this->givenFileName();
        $extensionList = $this->createMock(ExtensionList::class);
        $extensionList
            ->expects(self::once())
            ->method('isInList')
            ->with(self::FILE_NAME);
        $this->fileSystem->setForceDownloadExtensionList($extensionList);

        $this->fileSystem->getUrl(self::UUID);
    }

    public function test_ForceDownloadListAndInList_getUrl_shouldGetUrlAndForceDownload()
    {
        $this->givenMountSelected();
        $this->givenFileName();
        $extensionList = $this->createMock(ExtensionList::class);
        $extensionList->method('isInList')->willReturn(true);
        $this->fileSystem->setForceDownloadExtensionList($extensionList);
        $this->mount
            ->expects(self::once())
            ->method('getUrl')
            ->with(self::UUID, self::FILE_NAME, true);

        $this->fileSystem->getUrl(self::UUID);
    }

    public function test_givenId_delete_shouldGetFileName()
    {
        $this->givenMountSelected();
        $this->fileRepository
            ->expects(self::once())
            ->method('getFileName')
            ->with(self::UUID);

        $this->fileSystem->delete(self::UUID);
    }

    public function test_givenId_delete_shouldGetMountAssociatedWithId()
    {
        $this->givenMountSelected();

        $this->fileRepository
            ->expects(self::once())
            ->method('getFileMountType')
            ->with(self::UUID);

        $this->fileSystem->delete(self::UUID);
    }

    public function test_mountType_delete_shouldSelectMount()
    {
        $this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

        $this->mountSelector
            ->expects(self::once())
            ->method('selectMount')
            ->with(self::MOUNT_TYPE)
            ->willReturn($this->mount);

        $this->fileSystem->delete(self::UUID);
    }

    public function test_mountCouldNotHaveBeenSelected_delete_shouldThrowMountNotFoundException()
    {
        $this->mountSelector->method('selectMount')->willReturn(null);

        $this->expectException(MountNotFoundException::class);

        $this->fileSystem->delete(self::UUID);
    }

    public function test_mountSelect_delete_ShouldCheckIfMountHasId()
    {
        $this->givenFileName();
        $this->givenMountSelected();
        $this->mount
            ->expects(self::once())
            ->method('has')
            ->with(self::UUID, self::FILE_NAME);

        $this->fileSystem->delete(self::UUID);
    }

    public function test_mountHasFile_delete_ShouldDeleteInMount()
    {
        $this->givenMountSelected();
        $this->givenFileName();
        $this->givenMountHasFile();

        $this->mount
            ->expects(self::once())
            ->method('delete')
            ->with(self::UUID, self::FILE_NAME);

        $this->fileSystem->delete(self::UUID);
    }

    public function test_DeleteInMount_delete_ShouldDeleteInRepository()
    {
        $this->givenMountSelected();
        $this->givenMountHasFile();
        $this->mount
            ->method('delete')
            ->willReturn(self::HAS_FILE);
        $this->fileRepository
            ->expects(self::once())
            ->method('delete')
            ->with(self::UUID);

        $this->fileSystem->delete(self::UUID);
    }

    public function test_fileNotInMount_delete_ShouldNotDeleteInMountButStillDeleteInRepository()
    {
        $this->givenMountSelected();
        $this->givenFileName();
        $this->mount->method('has')->willReturn(false);
        $this->mount
            ->expects(self::never())
            ->method('delete');
        $this->fileRepository
            ->expects(self::once())
            ->method('delete')
            ->with(self::UUID);

        $this->fileSystem->delete(self::UUID);
    }

    public function test_DeleteFailed_delete_ShouldNotDeleteInRepository()
    {
        $this->givenMountSelected();
        $this->givenMountHasFile();
        $this->mount
            ->method('delete')
            ->willReturn(false);
        $this->fileRepository
            ->expects(self::never())
            ->method('delete');

        $this->fileSystem->delete(self::UUID);
    }

    public function test_givenId_deleteAsync_shouldGetFileName()
    {
        $this->givenMountSelected();
        $this->fileRepository
            ->expects(self::once())
            ->method('getFileName')
            ->with(self::UUID);

        $this->fileSystem->deleteAsync(self::UUID);
    }

    public function test_givenId_deleteAsync_shouldGetMountAssociatedWithId()
    {
        $this->givenMountSelected();

        $this->fileRepository
            ->expects(self::once())
            ->method('getFileMountType')
            ->with(self::UUID);

        $this->fileSystem->deleteAsync(self::UUID);
    }

    public function test_mountType_deleteAsync_shouldSelectMount()
    {
        $this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

        $this->mountSelector
            ->expects(self::once())
            ->method('selectMount')
            ->with(self::MOUNT_TYPE)
            ->willReturn($this->mount);

        $this->fileSystem->deleteAsync(self::UUID);
    }

    public function test_mountSelect_deleteAsync_ShouldCheckIfMountHasId()
    {
        $this->givenFileName();
        $this->givenMountSelected();
        $this->mount
            ->expects(self::once())
            ->method('hasAsync')
            ->with(self::UUID, self::FILE_NAME)
            ->willReturn(new RejectedPromise("test"));

        $this->fileSystem->deleteAsync(self::UUID);
    }

    public function test_hasPromise_deleteAsync_ShouldChainTwiceOnPromiseAndReturnFinalPromise(): void
    {
        $hasPromise = $this->createMock(PromiseInterface::class);
        $chainedPromise = $this->createMock(PromiseInterface::class);
        $expectedPromise = $this->createMock(PromiseInterface::class);
        $this->givenFileName();
        $this->givenMountSelected();
        $this->mount
            ->method('hasAsync')
            ->willReturn($hasPromise);
        $hasPromise
            ->expects(self::once())
            ->method('then')
            ->with(
                $this->isInstanceOf(Closure::class),
                null
            )
            ->willReturn($chainedPromise);
        $chainedPromise
            ->expects(self::once())
            ->method('then')
            ->with(
                $this->isInstanceOf(Closure::class),
                null
            )
            ->willReturn($expectedPromise);

        $actualPromise = $this->fileSystem->deleteAsync(self::UUID);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_mountHasFile_deleteAsync_ShouldDeleteAsyncInMount()
    {
        $this->givenMountSelected();
        $this->givenFileName();
        $this->givenMountHasFileAsync();
        $this->mount
            ->expects(self::once())
            ->method('deleteAsync')
            ->with(self::UUID, self::FILE_NAME);

        $this->fileSystem->deleteAsync(self::UUID);
        Utils::queue()->run();
    }

    public function test_didDelete_deleteAsync_ShouldDeleteInRepository()
    {
        $promise = new Promise();
        $this->givenMountSelected();
        $this->givenFileName();
        $this->givenMountHasFileAsync();
        $this->mount
            ->method('deleteAsync')
            ->willReturn(new FulfilledPromise(true));
        $this->fileRepository
            ->expects(self::once())
            ->method('delete')
            ->with(self::UUID);
        $this->fileSystem->deleteAsync(self::UUID);

        $promise->resolve(true);
        Utils::queue()->run();
    }

    public function test_didNotDelete_deleteAsync_ShouldNotDeleteInRepository()
    {
        $promise = new Promise();
        $this->givenMountSelected();
        $this->givenFileName();
        $this->givenMountHasFileAsync();
        $this->mount
            ->method('deleteAsync')
            ->willReturn(new FulfilledPromise(false));
        $this->fileRepository
            ->expects(self::never())
            ->method('delete');
        $this->fileSystem->deleteAsync(self::UUID);

        $promise->resolve(false);
        Utils::queue()->run();
    }

    public function test_fileNotInMount_deleteAsync_ShouldNotDeleteAsyncInMount()
    {
        $this->givenMountSelected();
        $this->givenFileName();
        $this->mount
            ->method('hasAsync')
            ->willReturn(new FulfilledPromise(false));
        $this->mount
            ->expects(self::never())
            ->method('deleteAsync');

        $this->fileSystem->deleteAsync(self::UUID);
        Utils::queue()->run();
    }

    public function test_fileNotInMount_deleteAsync_ShouldDeleteInRepository()
    {
        $this->givenMountSelected();
        $this->givenFileName();
        $this->mount
            ->method('hasAsync')
            ->willReturn(new FulfilledPromise(false));
        $this->fileRepository
            ->expects(self::once())
            ->method('delete')
            ->with(self::UUID);

        $this->fileSystem->deleteAsync(self::UUID);
        Utils::queue()->run();
    }

    public function test_mountCouldNotBeSelected_deleteAsync_shouldReturnRejectedPromise()
    {
        $this->mountSelector->method('selectMount')->willReturn(null);
        $expectedPromise = $this->createMock(RejectedPromise::class);
        $this->promiseFactory
            ->expects(self::once())
            ->method('createRejectedPromise')
            ->with(self::isInstanceOf(MountNotFoundException::class))
            ->willReturn($expectedPromise);

        $actualPromise = $this->fileSystem->deleteAsync(self::UUID);

        $this->assertSame($expectedPromise, $actualPromise);
    }

    public function test_repositoryThrowsException_deleteAsync_shouldReturnRejectedPromise()
    {
        $exception = new Exception(self::REPOSITORY_DELETE_EXCEPTION_MESSAGE);
        $this->givenMountSelected();
        $this->givenFileName();
        $this->givenMountHasFileAsync();
        $this->mount
            ->method('deleteAsync')
            ->willReturn(new FulfilledPromise(true));
        $this->fileRepository
            ->method('delete')
            ->willThrowException($exception);
        $actualReason = null;

        $actualPromise = $this->fileSystem->deleteAsync(self::UUID);
        $actualPromise->then(
            null,
            function ($reason) use (&$actualReason) {
                $actualReason = $reason;
            }
        );
        Utils::queue()->run();

        $this->assertEquals(PromiseInterface::REJECTED, $actualPromise->getState());
        $this->assertSame($actualReason, $exception);
    }

    public function test_givenId_retrieve_shouldMountAssociatedWithId()
    {
        $this->givenMountSelected();

        $this->fileRepository
            ->expects(self::once())
            ->method('getFileMountType')
            ->with(self::UUID);

        $this->fileSystem->retrieve(self::UUID);
    }

    public function test_mountType_retrieve_shouldSelectMount()
    {
        $this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

        $this->mountSelector
            ->expects(self::once())
            ->method('selectMount')
            ->with(self::MOUNT_TYPE)
            ->willReturn($this->mount);

        $this->fileSystem->retrieve(self::UUID);
    }

    public function test_mountCouldNotHaveBeenSelected_retrieve_shouldThrowMountNotFoundException()
    {
        $this->mountSelector->method('selectMount')->willReturn(null);

        $this->expectException(MountNotFoundException::class);

        $this->fileSystem->retrieve(self::UUID);
    }

    public function test_mount_retrieve_retreive()
    {
        $this->givenMountSelected();

        $this->mount
            ->expects(self::once())
            ->method('retrieve')
            ->with(self::UUID);

        $this->fileSystem->retrieve(self::UUID);
    }

    public function test_givenId_getMetadata_shouldMountAssociatedWithId()
    {
        $this->metadata = new Metadata();
        $this->mount->method('getMetadata')->willReturn($this->metadata);
        $this->file = $this->createMock(File::class);
        $this->mount->method('get')->willReturn($this->file);
        $this->givenMountSelected();

        $this->fileRepository
            ->expects(self::once())
            ->method('getFileMountType')
            ->with(self::UUID);

        $this->fileSystem->getMetadata(self::UUID);
    }

    public function test_mountType_getMetadata_shouldSelectMount()
    {
        $this->metadata = new Metadata();
        $this->mount->method('getMetadata')->willReturn($this->metadata);
        $this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

        $this->mountSelector
            ->expects(self::once())
            ->method('selectMount')
            ->with(self::MOUNT_TYPE)
            ->willReturn($this->mount);

        $this->fileSystem->getMetadata(self::UUID);
    }

    public function test_mountCouldNotHaveBeenSelected_getMetadata_shouldThrowMountNotFoundException()
    {
        $this->mountSelector->method('selectMount')->willReturn(null);

        $this->expectException(MountNotFoundException::class);

        $this->fileSystem->getMetadata(self::UUID);
    }

    public function test_mount_getMetadata_getMetadata()
    {
        $this->metadata = new Metadata();
        $this->givenMountSelected();
        $this->mount->method('getMetadata')->willReturn($this->metadata);

        $this->mount
            ->expects(self::once())
            ->method('getMetadata')
            ->with(self::UUID);

        $this->fileSystem->getMetadata(self::UUID);
    }

    public function test_metadata_getMetadata_ShouldGetFileName()
    {
        $this->metadata = new Metadata();
        $this->givenMountSelected();
        $this->mount->method('getMetadata')->willReturn($this->metadata);

        $this->fileRepository
            ->expects(self::once())
            ->method('getFileName')
            ->with(self::UUID);

        $this->fileSystem->getMetadata(self::UUID);
    }

    public function test_InternalMetadata_getMetadata_ShouldTranslateMetadata()
    {
        $this->metadata = new Metadata();
        $this->givenMountSelected();
        $this->mount->method('getMetadata')->willReturn($this->metadata);

        $this->metadataTranslator
            ->expects(self::once())
            ->method('translateInternalToExposed')
            ->with($this->metadata);

        $this->fileSystem->getMetadata(self::UUID);
    }

    public function test_mount_getMetadata_ShouldReturnMetadata()
    {
        $this->metadata = new Metadata();
        $this->givenMountSelected();
        $this->mount->method('getMetadata')->willReturn($this->metadata);

        $actualMetadata = $this->fileSystem->getMetadata(self::UUID);

        self::assertSame($actualMetadata, $actualMetadata);
    }

    public function test_givenId_get_shouldMountAssociatedWithId()
    {
        $this->givenWillReturnFile();
        $this->givenMountSelected();

        $this->fileRepository
            ->expects(self::once())
            ->method('getFileMountType')
            ->with(self::UUID);

        $this->fileSystem->get(self::UUID);
    }

    public function test_mountType_get_shouldSelectMount()
    {
        $this->givenWillReturnFile();
        $this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

        $this->mountSelector
            ->expects(self::once())
            ->method('selectMount')
            ->with(self::MOUNT_TYPE)
            ->willReturn($this->mount);

        $this->fileSystem->get(self::UUID);
    }

    public function test_mountCouldNotHaveBeenSelected_get_shouldThrowMountNotFoundException()
    {
        $this->givenWillReturnFile();
        $this->mountSelector->method('selectMount')->willReturn(null);

        $this->expectException(MountNotFoundException::class);

        $this->fileSystem->get(self::UUID);
    }

    public function test_mount_get_shouldGetFile()
    {
        $this->givenWillReturnFile();
        $this->givenMountSelected();

        $this->mount
            ->expects(self::once())
            ->method('get')
            ->with(self::UUID);

        $this->fileSystem->get(self::UUID);
    }

    public function test_File_get_shouldReturnFile()
    {
        $this->givenWillReturnFile();
        $this->givenMountSelected();

        $this->file = $this->fileSystem->get(self::UUID);

        self::assertInstanceOf(File::class, $this->file);
    }

    public function test_wrongTypeException_mountGet_shouldThrowException()
    {
        $this->givenWillThrowWrongTypeException();
        $this->givenMountSelected();

        $this->expectException(WrongTypeException::class);

        $this->file = $this->fileSystem->get(self::UUID);
    }

    public function test_copy_shouldGetFileMountType()
    {
        $this->givenMountSelected();
        $this->fileRepository
            ->expects(self::once())
            ->method('getFileMountType')
            ->with(self::UUID);

        $this->fileSystem->copy(self::UUID);
    }

    public function test_copy_shouldGetFileName()
    {
        $this->givenMountSelected();
        $this->fileRepository
            ->expects(self::once())
            ->method('getFileName')
            ->with(self::UUID);

        $this->fileSystem->copy(self::UUID);
    }

    public function test_ImportationMountType_copy_shouldSelectImportationMount()
    {
        $this->givenImporationMountType();
        $this->givenFileName();
        $this->givenFileInImportationMount();
        $this->givenMountSelected();
        $this->mountSelector
            ->expects(self::exactly(2))
            ->method('selectMount')
            ->with(self::IMPORTATION_MOUNT_TYPE);

        $this->fileSystem->copy(self::UUID);
    }

    public function test_selfContainedSourceMountType_copy_shouldSelectSourceMount()
    {
        $this->givenMountSelected();
        $this->givenDifferentSourceMount();
        $this->givenMountIsSelfContained();
        $this->mountSelector
            ->expects(self::once())
            ->method('selectMount')
            ->with(self::SOURCE_MOUNT_TYPE);

        $this->fileSystem->copy(self::UUID);
    }

    public function test_ImportationMountTypeAndFileName_copy_shouldAddNewFile()
    {
        $this->givenImporationMountType();
        $this->givenFileName();
        $this->givenFileInImportationMount();
        $this->givenMountSelected();
        $this->fileRepository
            ->expects(self::once())
            ->method('addNewFile')
            ->with(self::IMPORTATION_MOUNT_TYPE, self::FILE_NAME);

        $this->fileSystem->copy(self::UUID);
    }

    public function test_SelfContainedImportationMountTypeAndFileName_copy_shouldAddNewFile()
    {
        $this->givenImporationMountType();
        $this->givenFileName();
        $this->givenFileInImportationMount();
        $this->givenMountSelected();
        $this->givenMountIsSelfContained();
        $this->fileRepository
            ->expects(self::once())
            ->method('addNewFile')
            ->with(self::IMPORTATION_MOUNT_TYPE, self::FILE_NAME);

        $this->fileSystem->copy(self::UUID);
    }

    public function test_AddedFileAndSameSourceAndImporationMountTypes_copy_shouldCopyFile()
    {
        $this->givenImporationMountType();
        $this->givenFileName();
        $this->givenFileInImportationMount();
        $this->fileRepository->method('addNewFile')->willReturn(self::NEW_FILE_UUID);
        $this->givenMountSelected();
        $this->mount
            ->expects(self::once())
            ->method('copy')
            ->with(self::UUID, self::NEW_FILE_UUID, self::FILE_NAME);

        $this->fileSystem->copy(self::UUID);
    }

    public function test_FileCopied_copy_shouldReturnNewUuid()
    {
        $this->givenImporationMountType();
        $this->givenFileInImportationMount();
        $this->fileRepository->method('addNewFile')->willReturn(self::NEW_FILE_UUID);
        $this->givenMountSelected();

        $actualUuid = $this->fileSystem->copy(self::UUID);

        $this->assertSame(self::NEW_FILE_UUID, $actualUuid);
    }

    public function test_DifferentSourceAndImportationMountTypes_copy_shouldNotCallCopy()
    {
        $this->givenImporationMountType();
        $this->givenDifferentSourceMount();
        $this->givenSourceAndImporationMounts();
        $this->importationMount
            ->expects(self::never())
            ->method('copy');

        $this->fileSystem->copy(self::UUID);
    }

    public function test_DifferentSourceAndImportationMountTypes_copy_shouldSelectSourceMount()
    {
        $this->givenImporationMountType();
        $this->givenDifferentSourceMount();
        $this->givenSourceAndImporationMounts();
        $this->mountSelector
            ->expects(self::exactly(2))
            ->method('selectMount')
            ->with(
                ...self::withConsecutive(
                    [self::SOURCE_MOUNT_TYPE],
                    [self::IMPORTATION_MOUNT_TYPE]
                )
            );

        $this->fileSystem->copy(self::UUID);
    }

    public function test_SourceMount_copy_shouldGetFile()
    {
        $this->givenImporationMountType();
        $this->givenDifferentSourceMount();
        $this->givenSourceAndImporationMounts();
        $this->sourceMount
            ->expects(self::once())
            ->method('get')
            ->with(self::UUID);

        $this->fileSystem->copy(self::UUID);
    }

    public function test_File_copy_shouldReadStream()
    {
        $this->givenImporationMountType();
        $this->givenDifferentSourceMount();
        $this->givenSourceAndImporationMounts();
        $this->file
            ->expects(self::once())
            ->method('readStream');

        $this->fileSystem->copy(self::UUID);
    }

    public function test_Stream_copy_shouldPutStream()
    {
        $this->givenImporationMountType();
        $this->givenFileName();
        $this->givenDifferentSourceMount();
        $this->fileRepository->method('addNewFile')->willReturn(self::NEW_FILE_UUID);
        $this->givenSourceAndImporationMounts();
        $stream = tmpfile();
        $this->file->method('readStream')->willReturn($stream);
        $this->importationMount
            ->expects(self::once())
            ->method('putStream')
            ->with(self::NEW_FILE_UUID, $stream, self::FILE_NAME);

        $this->fileSystem->copy(self::UUID);
    }

    public function test_SelfContainedSourceMount_copy_shouldCallCopy()
    {
        $this->givenImporationMountType();
        $this->givenDifferentSourceMount();
        $this->givenSourceAndImporationMounts();
        $this->givenSourceMountIsSelfContained();
        $this->sourceMount
            ->expects(self::once())
            ->method('copy');
        $this->sourceMount
            ->expects(self::never())
            ->method('get');
        $this->importationMount
            ->expects(self::never())
            ->method('putStream');

        $this->fileSystem->copy(self::UUID);
    }

    public function test_StreamPut_copy_shouldReturnNewFileUuid()
    {
        $this->givenImporationMountType();
        $this->givenDifferentSourceMount();
        $this->fileRepository->method('addNewFile')->willReturn(self::NEW_FILE_UUID);
        $this->givenSourceAndImporationMounts();
        $stream = tmpfile();
        $this->file->method('readStream')->willReturn($stream);

        $actualUuid = $this->fileSystem->copy(self::UUID);

        $this->assertEquals(self::NEW_FILE_UUID, $actualUuid);
    }

    public function test_uuid_copyAsync_shouldGetChooserForFileId(): void
    {
        $this->givenDestinationChooser();
        $this->givenChooserCanUseCopy();
        $this->setupSourceMountAndFile();
        $this->setupCopyAsyncPromiseChain();
        $this->destinationChooserFactory
            ->expects(self::once())
            ->method('getChooserForFileId')
            ->with(self::UUID);

        $this->fileSystem->copyAsync(self::UUID);
    }

    public function test_chooser_copyAsync_shouldGetDestinationMountType(): void
    {
        $this->givenDestinationChooser();
        $this->givenChooserCanUseCopy();
        $this->setupSourceMountAndFile();
        $this->setupCopyAsyncPromiseChain();
        $this->destinationChooser
            ->expects(self::once())
            ->method('getDestinationMountType');

        $this->fileSystem->copyAsync(self::UUID);
    }

    public function test_uuid_copyAsync_shouldGetFileName(): void
    {
        $this->givenDestinationChooser();
        $this->givenChooserCanUseCopy();
        $this->setupSourceMountAndFile();
        $this->setupCopyAsyncPromiseChain();
        $this->fileRepository
            ->expects(self::once())
            ->method('getFileName')
            ->with(self::UUID);

        $this->fileSystem->copyAsync(self::UUID);
    }

    public function test_destinationMountTypeAndFileName_copyAsync_shouldAddNewFile(): void
    {
        $this->givenFileName();
        $this->givenDestinationChooser();
        $this->givenChooserCanUseCopy();
        $this->setupSourceMountAndFile();
        $this->setupCopyAsyncPromiseChain();
        $this->destinationChooser
            ->method('getDestinationMountType')
            ->willReturn(self::DESTINATION_MOUNT_TYPE);
        $this->fileRepository
            ->expects(self::once())
            ->method('addNewFile')
            ->with(
                self::DESTINATION_MOUNT_TYPE,
                self::FILE_NAME
            );

        $this->fileSystem->copyAsync(self::UUID);
    }

    public function test_chooser_copyAsync_shouldVerifyIfCopyCanBeUsed(): void
    {
        $this->givenDestinationChooser();
        $this->setupSourceMountAndFile();
        $this->setupCopyAsyncPromiseChain();
        $this->destinationChooser
            ->expects(self::once())
            ->method('canUseCopy')
            ->willReturn(true);

        $this->fileSystem->copyAsync(self::UUID);
    }

    public function test_canUseCopy_copyAsync_shouldCopyAsync(): void
    {
        $mountPromise = $this->createMock(PromiseInterface::class);
        $chainedPromise = $this->createMock(PromiseInterface::class);
        $this->givenFileName();
        $this->fileRepository
            ->method('addNewFile')
            ->willReturn(self::NEW_FILE_UUID);
        $destinationMount = $this->createMock(MountInterface::class);
        $this->givenDestinationChooser();
        $this->givenChooserCanUseCopy();
        $this->destinationChooser
            ->expects(self::once())
            ->method('getDestinationMount')
            ->willReturn($destinationMount);
        $destinationMount
            ->expects(self::once())
            ->method('copyAsync')
            ->with(
                self::UUID,
                self::NEW_FILE_UUID,
                self::FILE_NAME
            )
            ->willReturn($mountPromise);
        $mountPromise
            ->method('then')
            ->willReturn($chainedPromise);

        $this->fileSystem->copyAsync(self::UUID);
    }

    public function test_cannotUseCopy_copyAsync_shouldGetFileFromSourceMountAndPutStreamAsync(): void
    {
        $mountPromise = $this->createMock(PromiseInterface::class);
        $chainedPromise = $this->createMock(PromiseInterface::class);
        $file = $this->createMock(File::class);
        $file
            ->expects(self::once())
            ->method('readStream')
            ->willReturn(self::FILE_STREAM);
        $this->givenFileName();
        $this->fileRepository
            ->method('addNewFile')
            ->willReturn(self::NEW_FILE_UUID);
        $this->givenDestinationChooser();
        $this->destinationChooser
            ->method('canUseCopy')
            ->willReturn(false);
        $sourceMount = $this->createMock(MountInterface::class);
        $this->destinationChooser
            ->method('getSourceMount')
            ->willReturn($sourceMount);
        $sourceMount
            ->method('get')
            ->willReturn($file);
        $destinationMount = $this->createMock(MountInterface::class);
        $this->destinationChooser
            ->method('getDestinationMount')
            ->willReturn($destinationMount);
        $destinationMount
            ->expects(self::once())
            ->method('putStreamAsync')
            ->with(
                self::NEW_FILE_UUID,
                self::FILE_STREAM,
                self::FILE_NAME
            )
            ->willReturn($mountPromise);
        $mountPromise
            ->method('then')
            ->willReturn($chainedPromise);

        $this->fileSystem->copyAsync(self::UUID);
    }

    public function test_promise_copyAsync_shouldAddOnFulfilledCallbackAndReturnPromise(): void
    {
        $mountPromise = $this->createMock(PromiseInterface::class);
        $expectedPromise = $this->createMock(PromiseInterface::class);
        $this->givenFileName();
        $this->fileRepository
            ->method('addNewFile')
            ->willReturn(self::NEW_FILE_UUID);
        $destinationMount = $this->createMock(MountInterface::class);
        $this->givenDestinationChooser();
        $this->givenChooserCanUseCopy();
        $this->destinationChooser
            ->expects(self::once())
            ->method('getDestinationMount')
            ->willReturn($destinationMount);
        $destinationMount
            ->method('copyAsync')
            ->willReturn($mountPromise);
        $mountPromise
            ->expects(self::once())
            ->method('then')
            ->with(self::isInstanceOf(Closure::class))
            ->willReturn($expectedPromise);

        $actualPromise = $this->fileSystem->copyAsync(self::UUID);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_promiseFulfilled_copyAsync_shouldReturnDestinationId(): void
    {
        $this->givenDestinationChooser();
        $this->givenChooserCanUseCopy();
        $this->setupSourceMountAndFile();
        $this->fileRepository
            ->method('addNewFile')
            ->willReturn(self::NEW_FILE_UUID);
        $mountPromise = new FulfilledPromise(true);
        $mount = $this->createMock(MountInterface::class);
        $this->destinationChooser
            ->method('getDestinationMount')
            ->willReturn($mount);
        $mount
            ->method('copyAsync')
            ->willReturn($mountPromise);
        $called = false;
        $fulfilledValue = null;

        $promise = $this->fileSystem->copyAsync(self::UUID);
        $promise->then(function ($value) use (&$called, &$fulfilledValue) {
            $called = true;
            $fulfilledValue = $value;
        });
        Utils::queue()->run();

        self::assertTrue($called);
        self::assertEquals(self::NEW_FILE_UUID, $fulfilledValue);
    }

    public function test_Uuid_has_shouldGetFileMountType()
    {
        $this->fileRepository
            ->expects(self::once())
            ->method('getFileMountType')
            ->with(self::UUID);
        $this->givenMountSelected();

        $this->fileSystem->has(self::UUID);
    }

    public function test_Uuid_has_shouldGetFileName()
    {
        $this->fileRepository
            ->expects(self::once())
            ->method('getFileName')
            ->with(self::UUID);
        $this->givenMountSelected();

        $this->fileSystem->has(self::UUID);
    }

    public function test_MountType_has_shouldSelectMount()
    {
        $this->fileRepository
            ->method('getFileMountType')
            ->willReturn(self::MOUNT_TYPE);
        $this->mountSelector
            ->expects(self::once())
            ->method('selectMount')
            ->with(self::MOUNT_TYPE)
            ->willReturn($this->mount);

        $this->fileSystem->has(self::UUID);
    }

    public function test_Mount_has_shouldCheckAndReturnIfMountHasUuid()
    {
        $this->givenMountSelected();
        $this->fileRepository
            ->method('getFileName')
            ->willReturn(self::FILE_NAME);
        $this->mount
            ->expects(self::once())
            ->method('has')
            ->with(self::UUID, self::FILE_NAME)
            ->willReturn(self::HAS_FILE);

        $actualResult = $this->fileSystem->has(self::UUID);

        $this->assertSame(self::HAS_FILE, $actualResult);
    }

    public function test_Uuid_hasAsync_shouldGetFileMountType()
    {
        $this->fileRepository
            ->expects(self::once())
            ->method('getFileMountType')
            ->with(self::UUID);
        $this->givenMountSelected();

        $this->fileSystem->hasAsync(self::UUID);
    }

    public function test_Uuid_hasAsync_shouldGetFileName()
    {
        $this->fileRepository
            ->expects(self::once())
            ->method('getFileName')
            ->with(self::UUID);
        $this->givenMountSelected();

        $this->fileSystem->hasAsync(self::UUID);
    }

    public function test_MountType_hasAsync_shouldSelectMount()
    {
        $this->fileRepository
            ->method('getFileMountType')
            ->willReturn(self::MOUNT_TYPE);
        $this->mountSelector
            ->expects(self::once())
            ->method('selectMount')
            ->with(self::MOUNT_TYPE)
            ->willReturn($this->mount);

        $this->fileSystem->hasAsync(self::UUID);
    }

    public function test_Mount_hasAsync_shouldCheckAndReturnIfMountHasUuid()
    {
        $expectedPromise = $this->createMock(PromiseInterface::class);
        $this->givenMountSelected();
        $this->fileRepository
            ->method('getFileName')
            ->willReturn(self::FILE_NAME);
        $this->mount
            ->expects(self::once())
            ->method('hasAsync')
            ->with(self::UUID, self::FILE_NAME)
            ->willReturn($expectedPromise);

        $actualPromise = $this->fileSystem->hasAsync(self::UUID);

        $this->assertSame($expectedPromise, $actualPromise);
    }

    public function test_uuid_useDirectDownload_shouldGetFileMountType()
    {
        $this->fileRepository
            ->expects(self::once())
            ->method('getFileMountType')
            ->with(self::UUID);
        $this->givenMountSelected();

        $this->fileSystem->useDirectDownload(self::UUID);
    }

    public function test_mountType_useDirectDownload_shouldSelectMount()
    {
        $this->fileRepository
            ->method('getFileMountType')
            ->willReturn(self::MOUNT_TYPE);
        $this->mountSelector
            ->expects(self::once())
            ->method('selectMount')
            ->with(self::MOUNT_TYPE)
            ->willReturn($this->mount);

        $this->fileSystem->useDirectDownload(self::UUID);
    }

    public function test_mount_useDirectDownload_shouldReturnMountUseDirectDownload()
    {
        $this->givenMountSelected();
        $this->mount
            ->expects(self::once())
            ->method('useDirectDownload')
            ->willReturn(true);

        $actualValue = $this->fileSystem->useDirectDownload(self::UUID);

        $this->assertSame(true, $actualValue);
    }

    private function givenWillReturnFile()
    {
        $this->metadata = new Metadata();
        $this->mount->method('getMetadata')->willReturn($this->metadata);
        $this->file = $this->createMock(File::class);
        $this->mount->method('get')->willReturn($this->file);
    }

    private function givenWillThrowWrongTypeException()
    {
        $this->metadata = new Metadata();
        $this->mount->method('getMetadata')->willReturn($this->metadata);
        $this->file = $this->createMock(File::class);
        $this->mount->method('get')->willThrowException(new WrongTypeException('expected', 'actual'));
    }

    protected function givenImporationMountType()
    {
        $this->mountSelector->method('getImportationMountType')->willReturn(self::IMPORTATION_MOUNT_TYPE);
    }

    protected function givenFileName()
    {
        $this->fileRepository->method('getFileName')->willReturn(self::FILE_NAME);
    }

    protected function givenFileInImportationMount()
    {
        $this->fileRepository->method('getFileMountType')->willReturn(self::IMPORTATION_MOUNT_TYPE);
    }

    protected function givenDifferentSourceMount()
    {
        $this->fileRepository->method('getFileMountType')->willReturn(self::SOURCE_MOUNT_TYPE);
    }

    protected function givenSourceAndImporationMounts()
    {
        $this->file = $this->createMock(File::class);
        $this->sourceMount = $this->createMock(MountInterface::class);
        $this->sourceMount->method('get')->willReturn($this->file);
        $this->importationMount = $this->createMock(MountInterface::class);
        $this->mountSelector->method('selectMount')->willReturnMap([
            [self::SOURCE_MOUNT_TYPE, $this->sourceMount],
            [self::IMPORTATION_MOUNT_TYPE, $this->importationMount]
        ]);
    }

    protected function givenMountSelected()
    {
        $this->mountSelector->method('selectMount')->willReturn($this->mount);
    }

    protected function givenMountIsSelfContained()
    {
        $this->mount->method('isSelfContained')->willReturn(true);
    }

    protected function givenSourceMountIsSelfContained()
    {
        $this->sourceMount->method('isSelfContained')->willReturn(true);
    }

    protected function givenMountHasFile()
    {
        $this->mount->method('has')->willReturn(true);
    }

    protected function givenMountHasFileAsync()
    {
        $this->mount->method('hasAsync')->willReturn(new FulfilledPromise(true));
    }

    protected function buildMountPutAsyncPromiseChain(): PromiseInterface&MockObject
    {
        $mountPromise = $this->createMock(PromiseInterface::class);
        $chainedPromise = $this->createMock(PromiseInterface::class);
        $this->mount
            ->method('putAsync')
            ->willReturn($mountPromise);
        $mountPromise
            ->method('then')
            ->willReturn($chainedPromise);
        return $mountPromise;
    }

    protected function buildMountPutStreamAsyncPromiseChain(): PromiseInterface&MockObject
    {
        $mountPromise = $this->createMock(PromiseInterface::class);
        $chainedPromise = $this->createMock(PromiseInterface::class);
        $this->mount
            ->method('putStreamAsync')
            ->willReturn($mountPromise);
        $mountPromise
            ->method('then')
            ->willReturn($chainedPromise);
        return $mountPromise;
    }

    protected function givenDestinationChooser(): void
    {
        $this->destinationChooser = $this->createMock(DestinationChooser::class);
        $this->destinationChooserFactory
            ->method('getChooserForFileId')
            ->willReturn($this->destinationChooser);
    }

    protected function setupSourceMountAndFile(): void
    {
        $file = $this->createMock(File::class);
        $file
            ->method('readStream')
            ->willReturn(self::FILE_STREAM);
        $sourceMount = $this->createMock(MountInterface::class);
        $this->destinationChooser
            ->method('getSourceMount')
            ->willReturn($sourceMount);
        $sourceMount
            ->method('get')
            ->willReturn($file);
    }

    protected function givenChooserCanUseCopy(): void
    {
        $this->destinationChooser
            ->method('canUseCopy')
            ->willReturn(true);
    }

    protected function setupCopyAsyncPromiseChain(): void
    {
        $mountPromise = $this->createMock(PromiseInterface::class);
        $copyAsyncPromise = $this->createMock(PromiseInterface::class);
        $mount = $this->createMock(MountInterface::class);
        $this->destinationChooser
            ->method('getDestinationMount')
            ->willReturn($mount);
        $mount
            ->method('copyAsync')
            ->willReturn($mountPromise);
        $mountPromise
            ->method('then')
            ->willReturn($copyAsyncPromise);
    }
}
