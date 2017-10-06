<?php
namespace Kronos\Tests\FileSystem;

use Kronos\FileSystem\Exception\FileCantBeWrittenException;
use Kronos\FileSystem\Exception\MountNotFoundException;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Internal\Metadata;
use Kronos\FileSystem\File\Translator\MetadataTranslator;
use Kronos\FileSystem\FileRepositoryInterface;
use Kronos\FileSystem\FileSystem;
use Kronos\FileSystem\Mount\MountInterface;
use Kronos\FileSystem\Mount\Selector;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class FileSystemTest extends PHPUnit_Framework_TestCase{

	const A_FILE_PATH = 'A_FILE_PATH';
	const FILE_NAME = 'FILE_NAME';
	const MOUNT_TYPE = 'MOUNT_TYPE';
	const UUID = 'UUID';
	const A_SIGNED_URL = 'A_SIGNED_URL';

	/**
	 * @var File|PHPUnit_Framework_MockObject_MockObject
	 */
	private $file;

	/**
	 * @var Metadata|PHPUnit_Framework_MockObject_MockObject
	 */
	private $metadata;

	/**
	 * @var Selector|PHPUnit_Framework_MockObject_MockObject
	 */
	private $mountSelector;

	/**
	 * @var FileRepositoryInterface|PHPUnit_Framework_MockObject_MockObject
	 */
	private $fileRepository;

	/**
	 * @var FileSystem
	 */
	private $fileSystem;

	/**
	 * @var MountInterface|PHPUnit_Framework_MockObject_MockObject
	 */
	private $mount;

	/**
	 * @var MetadataTranslator|PHPUnit_Framework_MockObject_MockObject
	 */
	private $metadataTranslator;

	public function setUp(){

		$this->mount = $this->getMockWithoutInvokingTheOriginalConstructor(MountInterface::class);

		$this->metadataTranslator = $this->getMockWithoutInvokingTheOriginalConstructor(MetadataTranslator::class);
		$this->mountSelector = $this->getMockWithoutInvokingTheOriginalConstructor(Selector::class);
		$this->fileRepository = $this->getMockWithoutInvokingTheOriginalConstructor(FileRepositoryInterface::class);

		$this->fileSystem = new FileSystem($this->mountSelector,$this->fileRepository,$this->metadataTranslator);
	}

	public function tearDown() {
		unset($this->metadata);
		unset($this->file);
	}

	public function test_resource_put_shouldGetImportationMount(){
		$this->mount->method('put')->willReturn(true);

		$this->mountSelector
			->expects(self::once())
			->method('getImportationMount')
			->willReturn($this->mount);

		$this->fileSystem->put(self::A_FILE_PATH,self::FILE_NAME);
	}

	public function test_mount_put_shouldAddNewFile(){
		$this->mount->method('put')->willReturn(true);
		$this->mount->method('getMountType')->willReturn(self::MOUNT_TYPE);
		$this->mountSelector->method('getImportationMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::once())
			->method('addNewFile')
			->with(self::MOUNT_TYPE,self::FILE_NAME);

		$this->fileSystem->put(self::A_FILE_PATH,self::FILE_NAME);
	}

	public function test_mountAndUuid_put_putFile(){
		$this->mount->method('put')->willReturn(true);
		$this->mountSelector->method('getImportationMount')->willReturn($this->mount);
		$this->fileRepository->method('addNewFile')->willReturn(self::UUID);

		$this->mount
			->expects(self::once())
			->method('put')
			->with(self::UUID,self::A_FILE_PATH);

		$this->fileSystem->put(self::A_FILE_PATH,self::FILE_NAME);
	}

	public function test_fileAsBeenWritten_put_shouldReturnFileUuid(){
		$this->mount->method('put')->willReturn(true);
		$this->mountSelector->method('getImportationMount')->willReturn($this->mount);
		$this->fileRepository->method('addNewFile')->willReturn(self::UUID);

		$actualFileUuid = $this->fileSystem->put(self::A_FILE_PATH,self::FILE_NAME);

		self::assertSame(self::UUID,$actualFileUuid);
	}

	public function test_putHaveNotBeenSucessfull_put_shouldThrowException(){
		$this->mountSelector->method('getImportationMount')->willReturn($this->mount);
		$this->mount->method('put')->willReturn(false);
		$this->fileRepository->method('addNewFile')->willReturn(self::UUID);

		$this->expectException(FileCantBeWrittenException::class);

		$this->fileSystem->put(self::A_FILE_PATH,self::FILE_NAME);
	}

	public function test_putHaveNotBeenSucessfull_put_shouldDeleteNewUuid(){
		$this->mountSelector->method('getImportationMount')->willReturn($this->mount);
		$this->mount->method('put')->willReturn(false);
		$this->fileRepository->method('addNewFile')->willReturn(self::UUID);

		$this->expectException(FileCantBeWrittenException::class);

		$this->fileRepository
			->expects(self::once())
			->method('delete')
			->with(self::UUID);

		$this->fileSystem->put(self::A_FILE_PATH,self::FILE_NAME);
	}

	public function test_givenId_getUrl_shouldMountAssociatedWithId(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::once())
			->method('getFileMountType')
			->with(self::UUID);

		$this->fileSystem->getUrl(self::UUID, self::FILE_NAME);
	}

	public function test_mountType_getUrl_shouldSelectMount(){
		$this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

		$this->mountSelector
			->expects(self::once())
			->method('selectMount')
			->with(self::MOUNT_TYPE)
			->willReturn($this->mount);

		$this->fileSystem->getUrl(self::UUID, self::FILE_NAME);
	}

	public function test_mountSelected_getUrl_shouldGetUrl(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount
			->expects(self::once())
			->method('getUrl')
			->with(self::UUID);

		$this->fileSystem->getUrl(self::UUID, self::FILE_NAME);
	}

	public function test_mountCouldNotHaveBeenSelected_getUrl_shouldThrowMountNotFoundException(){

		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(MountNotFoundException::class);

		$this->fileSystem->getUrl(self::UUID, self::FILE_NAME);
	}

	public function test_signedUlr_getUrl_shouldReturnSignedUlr(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount->method('getUrl')->willReturn(self::A_SIGNED_URL);

		$actualSignedUrl = $this->fileSystem->getUrl(self::UUID, self::FILE_NAME);

		self::assertSame(self::A_SIGNED_URL,$actualSignedUrl);
	}

	public function test_givenId_delete_shouldMountAssociatedWithId(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::once())
			->method('getFileMountType')
			->with(self::UUID);

		$this->fileSystem->delete(self::UUID, self::FILE_NAME);
	}

	public function test_mountType_delete_shouldSelectMount(){
		$this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

		$this->mountSelector
			->expects(self::once())
			->method('selectMount')
			->with(self::MOUNT_TYPE)
			->willReturn($this->mount);

		$this->fileSystem->delete(self::UUID, self::FILE_NAME);
	}

	public function test_mountCouldNotHaveBeenSelected_delete_shouldThrowMountNotFoundException(){

		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(MountNotFoundException::class);

		$this->fileSystem->delete(self::UUID, self::FILE_NAME);
	}

	public function test_mountSelected_delete_delete(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount
			->expects(self::once())
			->method('delete')
			->with(self::UUID);

		$this->fileSystem->delete(self::UUID, self::FILE_NAME);
	}

	public function test_givenId_retrieve_shouldMountAssociatedWithId(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::once())
			->method('getFileMountType')
			->with(self::UUID);

		$this->fileSystem->retrieve(self::UUID, self::FILE_NAME);
	}

	public function test_mountType_retrieve_shouldSelectMount(){
		$this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

		$this->mountSelector
			->expects(self::once())
			->method('selectMount')
			->with(self::MOUNT_TYPE)
			->willReturn($this->mount);

		$this->fileSystem->retrieve(self::UUID, self::FILE_NAME);
	}

	public function test_mountCouldNotHaveBeenSelected_retrieve_shouldThrowMountNotFoundException(){
		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(MountNotFoundException::class);

		$this->fileSystem->retrieve(self::UUID, self::FILE_NAME);
	}

	public function test_mount_retrieve_retreive(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount
			->expects(self::once())
			->method('retrieve')
			->with(self::UUID);

		$this->fileSystem->retrieve(self::UUID, self::FILE_NAME);
	}

	public function test_givenId_getMetadata_shouldMountAssociatedWithId(){
		$this->metadata = new Metadata();
		$this->mount->method('getMetadata')->willReturn($this->metadata);
		$this->file = $this->getMockWithoutInvokingTheOriginalConstructor(File::class);
		$this->mount->method('get')->willReturn($this->file);
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::once())
			->method('getFileMountType')
			->with(self::UUID);

		$this->fileSystem->getMetadata(self::UUID, self::FILE_NAME);
	}

	public function test_mountType_getMetadata_shouldSelectMount(){
		$this->metadata = new Metadata();
		$this->mount->method('getMetadata')->willReturn($this->metadata);
		$this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

		$this->mountSelector
			->expects(self::once())
			->method('selectMount')
			->with(self::MOUNT_TYPE)
			->willReturn($this->mount);

		$this->fileSystem->getMetadata(self::UUID, self::FILE_NAME);
	}

	public function test_mountCouldNotHaveBeenSelected_getMetadata_shouldThrowMountNotFoundException(){
		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(MountNotFoundException::class);

		$this->fileSystem->getMetadata(self::UUID, self::FILE_NAME);
	}

	public function test_mount_getMetadata_getMetadata(){
		$this->metadata = new Metadata();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mount->method('getMetadata')->willReturn($this->metadata);

		$this->mount
			->expects(self::once())
			->method('getMetadata')
			->with(self::UUID);

		$this->fileSystem->getMetadata(self::UUID, self::FILE_NAME);
	}

	public function test_metadata_getMetadata_ShouldGetFileName(){
		$this->metadata = new Metadata();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mount->method('getMetadata')->willReturn($this->metadata);

		$this->fileRepository
			->expects(self::once())
			->method('getFileName')
			->with(self::UUID);

		$this->fileSystem->getMetadata(self::UUID, self::FILE_NAME);
	}

	public function test_InternalMetadata_getMetadata_ShouldTranslateMetadata(){
		$this->metadata = new Metadata();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mount->method('getMetadata')->willReturn($this->metadata);

		$this->metadataTranslator
			->expects(self::once())
			->method('translateInternalToExposed')
			->with($this->metadata);

		$this->fileSystem->getMetadata(self::UUID, self::FILE_NAME);
	}

	public function test_mount_getMetadata_ShouldReturnMetadata(){
		$this->metadata = new Metadata();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mount->method('getMetadata')->willReturn($this->metadata);

		$actualMetadata = $this->fileSystem->getMetadata(self::UUID, self::FILE_NAME);

		self::assertSame($actualMetadata,$actualMetadata);
	}


	public function test_givenId_get_shouldMountAssociatedWithId(){
		$this->givenWillReturnFile();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::exactly(2))
			->method('getFileMountType')
			->with(self::UUID);

		$this->fileSystem->get(self::UUID, self::FILE_NAME);
	}

	public function test_mountType_get_shouldSelectMount(){
		$this->givenWillReturnFile();
		$this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

		$this->mountSelector
			->expects(self::exactly(2))
			->method('selectMount')
			->with(self::MOUNT_TYPE)
			->willReturn($this->mount);

		$this->fileSystem->get(self::UUID, self::FILE_NAME);
	}

	public function test_mountCouldNotHaveBeenSelected_get_shouldThrowMountNotFoundException(){
		$this->givenWillReturnFile();
		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(MountNotFoundException::class);

		$this->fileSystem->get(self::UUID, self::FILE_NAME);
	}

	public function test_mount_get_shouldGetFile(){
		$this->givenWillReturnFile();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount
			->expects(self::once())
			->method('get')
			->with(self::UUID);

		$this->fileSystem->get(self::UUID, self::FILE_NAME);
	}

	public function test_mount_get_shouldGetMetadata(){
		$this->givenWillReturnFile();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount
			->expects(self::once())
			->method('getMetadata')
			->with(self::UUID);

		$this->fileSystem->get(self::UUID, self::FILE_NAME);
	}

	public function test_File_get_shouldReturnFile(){
		$this->givenWillReturnFile();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->file = $this->fileSystem->get(self::UUID, self::FILE_NAME);

		self::assertInstanceOf(File::class,$this->file);
	}

	public function test_Metadata_get_shouldBeTheMetadataInFileObject(){
		$this->metadataTranslator->method('translateInternalToExposed')->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(\Kronos\FileSystem\File\Metadata::class));
		$this->givenWillReturnFile();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$file = $this->fileSystem->get(self::UUID, self::FILE_NAME);

		self::assertInstanceOf(\Kronos\FileSystem\File\Metadata::class,$file->metadata);
	}

	private function givenWillReturnFile(){
		$this->metadata = new Metadata();
		$this->mount->method('getMetadata')->willReturn($this->metadata);
		$this->file = $this->getMockWithoutInvokingTheOriginalConstructor(File::class);
		$this->mount->method('get')->willReturn($this->file);
	}
}