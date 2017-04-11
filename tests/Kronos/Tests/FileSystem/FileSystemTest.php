<?php
namespace Kronos\Tests\FileSystem;

use Kronos\FileSystem\Exception\FileCantBeWrittenException;
use Kronos\FileSystem\Exception\FileNotFoundException;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Metadata;
use Kronos\FileSystem\FileRepositoryInterface;
use Kronos\FileSystem\FileSystem;
use Kronos\FileSystem\Mount\MountInterface;
use Kronos\FileSystem\Mount\Selector;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class FileSystemTest extends PHPUnit_Framework_TestCase{

	const A_RESOURCE = 'A_RESOURCE';
	const MOUNT_TYPE = 'MOUNT_TYPE';
	const UUID = 'UUID';
	const A_SIGNED_URL = 'A_SIGNED_URL';

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

	public function setUp(){

		$this->mount = $this->getMockWithoutInvokingTheOriginalConstructor(MountInterface::class);

		$this->mountSelector = $this->getMockWithoutInvokingTheOriginalConstructor(Selector::class);
		$this->fileRepository = $this->getMockWithoutInvokingTheOriginalConstructor(FileRepositoryInterface::class);

		$this->fileSystem = new FileSystem($this->mountSelector,$this->fileRepository);
	}

	public function test_resource_put_shouldGetImportationMount(){
		$this->mount->method('write')->willReturn(true);

		$this->mountSelector
			->expects(self::once())
			->method('getImportationMount')
			->willReturn($this->mount);

		$this->fileSystem->put(self::A_RESOURCE);
	}

	public function test_mount_put_shouldAddNewFile(){
		$this->mount->method('write')->willReturn(true);
		$this->mount->method('getMountType')->willReturn(self::MOUNT_TYPE);
		$this->mountSelector->method('getImportationMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::once())
			->method('addNewFile')
			->with(self::MOUNT_TYPE);

		$this->fileSystem->put(self::A_RESOURCE);
	}

	public function test_mountAndUuid_put_writeFile(){
		$this->mount->method('write')->willReturn(true);
		$this->mountSelector->method('getImportationMount')->willReturn($this->mount);
		$this->fileRepository->method('addNewFile')->willReturn(self::UUID);

		$this->mount
			->expects(self::once())
			->method('write')
			->with(self::UUID,self::A_RESOURCE);

		$this->fileSystem->put(self::A_RESOURCE);
	}

	public function test_fileAsBeenWritten_put_shouldReturnFileUuid(){
		$this->mount->method('write')->willReturn(true);
		$this->mountSelector->method('getImportationMount')->willReturn($this->mount);
		$this->fileRepository->method('addNewFile')->willReturn(self::UUID);

		$actualFileUuid = $this->fileSystem->put(self::A_RESOURCE);

		self::assertSame(self::UUID,$actualFileUuid);
	}

	public function test_writeHaveNotBeenSucessfull_put_shouldThrowException(){
		$this->mountSelector->method('getImportationMount')->willReturn($this->mount);
		$this->mount->method('write')->willReturn(false);
		$this->fileRepository->method('addNewFile')->willReturn(self::UUID);

		$this->expectException(FileCantBeWrittenException::class);

		$this->fileSystem->put(self::A_RESOURCE);
	}

	public function test_writeHaveNotBeenSucessfull_put_shouldDeleteNewUuid(){
		$this->mountSelector->method('getImportationMount')->willReturn($this->mount);
		$this->mount->method('write')->willReturn(false);
		$this->fileRepository->method('addNewFile')->willReturn(self::UUID);

		$this->expectException(FileCantBeWrittenException::class);

		$this->fileRepository
			->expects(self::once())
			->method('delete')
			->with(self::UUID);

		$this->fileSystem->put(self::A_RESOURCE);
	}

	public function test_givenId_getDownloadableLink_shouldMountAssociatedWithId(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::once())
			->method('getFileMountType')
			->with(self::UUID);

		$this->fileSystem->getDownloadableLink(self::UUID);
	}

	public function test_mountType_getDownloadableLink_shouldSelectMount(){
		$this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

		$this->mountSelector
			->expects(self::once())
			->method('selectMount')
			->with(self::MOUNT_TYPE)
			->willReturn($this->mount);

		$this->fileSystem->getDownloadableLink(self::UUID);
	}

	public function test_mountSelected_getDownloadableLink_getSignedUrl(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount
			->expects(self::once())
			->method('getSignedUrl')
			->with(self::UUID);

		$this->fileSystem->getDownloadableLink(self::UUID);
	}

	public function test_mountCouldNotHaveBeenSelected_getDownloadableLink_shouldThrowFileNotFoundException(){

		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(FileNotFoundException::class);

		$this->fileSystem->getDownloadableLink(self::UUID);
	}

	public function test_signedUlr_getDownloadableLink_shouldReturnSignedUlr(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount->method('getSignedUrl')->willReturn(self::A_SIGNED_URL);

		$actualSignedUrl = $this->fileSystem->getDownloadableLink(self::UUID);

		self::assertSame(self::A_SIGNED_URL,$actualSignedUrl);
	}

	public function test_givenId_delete_shouldMountAssociatedWithId(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::once())
			->method('getFileMountType')
			->with(self::UUID);

		$this->fileSystem->delete(self::UUID);
	}

	public function test_mountType_delete_shouldSelectMount(){
		$this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

		$this->mountSelector
			->expects(self::once())
			->method('selectMount')
			->with(self::MOUNT_TYPE)
			->willReturn($this->mount);

		$this->fileSystem->delete(self::UUID);
	}

	public function test_mountCouldNotHaveBeenSelected_delete_shouldThrowFileNotFoundException(){

		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(FileNotFoundException::class);

		$this->fileSystem->delete(self::UUID);
	}

	public function test_mountSelected_delete_delete(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount
			->expects(self::once())
			->method('delete')
			->with(self::UUID);

		$this->fileSystem->delete(self::UUID);
	}

	public function test_givenId_retrieve_shouldMountAssociatedWithId(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::once())
			->method('getFileMountType')
			->with(self::UUID);

		$this->fileSystem->retrieve(self::UUID);
	}

	public function test_mountType_retrieve_shouldSelectMount(){
		$this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

		$this->mountSelector
			->expects(self::once())
			->method('selectMount')
			->with(self::MOUNT_TYPE)
			->willReturn($this->mount);

		$this->fileSystem->retrieve(self::UUID);
	}

	public function test_mountCouldNotHaveBeenSelected_retrieve_shouldThrowFileNotFoundException(){
		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(FileNotFoundException::class);

		$this->fileSystem->retrieve(self::UUID);
	}

	public function test_mount_retrieve_retreive(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount
			->expects(self::once())
			->method('retrieve')
			->with(self::UUID);

		$this->fileSystem->retrieve(self::UUID);
	}

	public function test_givenId_getMetadata_shouldMountAssociatedWithId(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::once())
			->method('getFileMountType')
			->with(self::UUID);

		$this->fileSystem->getMetadata(self::UUID);
	}

	public function test_mountType_getMetadata_shouldSelectMount(){
		$this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

		$this->mountSelector
			->expects(self::once())
			->method('selectMount')
			->with(self::MOUNT_TYPE)
			->willReturn($this->mount);

		$this->fileSystem->getMetadata(self::UUID);
	}

	public function test_mountCouldNotHaveBeenSelected_getMetadata_shouldThrowFileNotFoundException(){
		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(FileNotFoundException::class);

		$this->fileSystem->getMetadata(self::UUID);
	}

	public function test_mount_getMetadata_getMetadata(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount
			->expects(self::once())
			->method('getMetadata')
			->with(self::UUID);

		$this->fileSystem->getMetadata(self::UUID);
	}

	public function test_mount_getMetadata_ShouldReturnMetadata(){
		$metadata = new Metadata();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mount->method('getMetadata')->willReturn($metadata);

		$actualMetadata = $this->fileSystem->getMetadata(self::UUID);

		self::assertSame($actualMetadata,$actualMetadata);
	}


	public function test_givenId_get_shouldMountAssociatedWithId(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::once())
			->method('getFileMountType')
			->with(self::UUID);

		$this->fileSystem->get(self::UUID);
	}

	public function test_mountType_get_shouldSelectMount(){
		$this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

		$this->mountSelector
			->expects(self::once())
			->method('selectMount')
			->with(self::MOUNT_TYPE)
			->willReturn($this->mount);

		$this->fileSystem->get(self::UUID);
	}

	public function test_mountCouldNotHaveBeenSelected_get_shouldThrowFileNotFoundException(){
		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(FileNotFoundException::class);

		$this->fileSystem->get(self::UUID);
	}

	public function test_mount_get_shouldGetRessource(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount
			->expects(self::once())
			->method('getResource')
			->with(self::UUID);

		$this->fileSystem->get(self::UUID);
	}

	public function test_mount_get_shouldGetMetadata(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount
			->expects(self::once())
			->method('getMetadata')
			->with(self::UUID);

		$this->fileSystem->get(self::UUID);
	}

	public function test_File_get_shouldReturnFile(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$file = $this->fileSystem->get(self::UUID);

		self::assertInstanceOf(File::class,$file);
	}

	public function test_Resource_get_shouldBeTheResourceInFileObject(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mount->method('getResource')->willReturn(self::A_RESOURCE);

		$file = $this->fileSystem->get(self::UUID);

		self::assertSame(self::A_RESOURCE,$file->resource);
	}

	public function test_Metadata_get_shouldBeTheMetadataInFileObject(){
		$metadata = new Metadata();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mount->method('getMetadata')->willReturn($metadata);

		$file = $this->fileSystem->get(self::UUID);

		self::assertSame($metadata,$file->metadata);
	}


}