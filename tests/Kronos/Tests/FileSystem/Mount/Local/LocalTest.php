<?php
namespace Kronos\Tests\FileSystem\Mount\Local;


use DoctrineTest\InstantiatorTestAsset\PharAsset;
use Kronos\FileSystem\File\Metadata;
use Kronos\FileSystem\Mount\PathGeneratorInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class LocalTest extends PHPUnit_Framework_TestCase{

	const UUID = 'UUID';
	const A_PATH = 'A_PATH';
	const A_STREAM = 'A_STREAM';
	const A_LOCATION = 'A_LOCATION';
	const AN_URI = 'AN_URI';
	const A_RESOURCE = 'A_RESOURCE';

	/**
	 * @var PathGeneratorInterface|PHPUnit_Framework_MockObject_MockObject
	 */
	private $pathGenerator;

	/**
	 * @var Local|PHPUnit_Framework_MockObject_MockObject
	 */
	private $localAdaptor;

	/**
	 * @var FileSystem|PHPUnit_Framework_MockObject_MockObject
	 */
	private $fileSystem;

	/**
	 * @var \Kronos\FileSystem\Mount\Local\Local
	 */
	private $localMount;

	public function setUp(){
		$this->fileSystem = $this->getMockWithoutInvokingTheOriginalConstructor(Filesystem::class);
		$this->localAdaptor = $this->getMockWithoutInvokingTheOriginalConstructor(Local::class);
		$this->pathGenerator = $this->getMockWithoutInvokingTheOriginalConstructor(PathGeneratorInterface::class);

		$this->fileSystem->method('getAdapter')->willReturn($this->localAdaptor);

		$this->localMount = new \Kronos\FileSystem\Mount\Local\Local($this->pathGenerator,$this->fileSystem);
	}

	public function test_uuid_getResource_shouldGetPathOfFile(){
		$this->pathGenerator
			->expects(self::once())
			->method('generatePath')
			->with(self::UUID);

		$this->localMount->getResource(self::UUID);
	}

	public function test_path_getResource_shouldReadStream(){
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

		$this->fileSystem
			->expects(self::once())
			->method('readStream')
			->with(self::A_PATH);

		$this->localMount->getResource(self::UUID);
	}

	public function test_noStream_getResource_shouldReturnEmptyString(){
		$this->fileSystem->method('readStream')->willReturn(false);

		$stream = $this->localMount->getResource(self::UUID);

		$this->assertFalse($stream);
	}

	public function test_stream_getResource_shouldReturnStream(){
		$this->fileSystem->method('readStream')->willReturn(self::A_STREAM);

		$stream = $this->localMount->getResource(self::UUID);

		$this->assertEquals(self::A_STREAM,$stream);
	}

	public function test_uuid_delete_shouldGetPathOfFile(){
		$this->pathGenerator
			->expects(self::once())
			->method('generatePath')
			->with(self::UUID);

		$this->localMount->delete(self::UUID);
	}

	public function test_path_delete_shouldDeleteFile(){
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

		$this->fileSystem
			->expects(self::once())
			->method('delete')
			->with(self::A_PATH);

		$this->localMount->delete(self::UUID);
	}

	public function test_FileDeleted_delete_shouldReturnTrue(){

		$this->fileSystem->method('delete')->willReturn(true);

		$deleted = $this->localMount->delete(self::UUID);

		$this->assertTrue($deleted);
	}

	public function test_FileNotDeleted_delete_shouldReturnFalse(){

		$this->fileSystem->method('delete')->willReturn(false);

		$deleted = $this->localMount->delete(self::UUID);

		$this->assertFalse($deleted);
	}

	public function test_uuid_write_shouldGetPathOfFile(){
		$this->pathGenerator
			->expects(self::once())
			->method('generatePath')
			->with(self::UUID);

		$this->localMount->write(self::UUID,self::A_RESOURCE);
	}

	public function test_path_write_shouldWrite(){
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

		$this->fileSystem
			->expects(self::once())
			->method('writeStream')
			->with(self::A_PATH,self::A_RESOURCE);

		$this->localMount->write(self::UUID,self::A_RESOURCE);
	}

	public function test_FileWritten_write_shouldReturnTrue(){

		$this->fileSystem->method('writeStream')->willReturn(true);

		$written = $this->localMount->write(self::UUID,self::A_RESOURCE);

		$this->assertTrue($written);
	}

	public function test_FileNotWritten_write_shouldReturnFalse(){

		$this->fileSystem->method('writeStream')->willReturn(false);

		$written = $this->localMount->write(self::UUID,self::A_RESOURCE);

		$this->assertFalse($written);
	}

	public function test_getMountType_ShouldReutrnMountType(){
		$actualMountType = $this->localMount->getMountType();

		self::assertEquals(\Kronos\FileSystem\Mount\Local\Local::MOUNT_TYPE,$actualMountType);
	}

	public function test_uuid_getMetadata_shouldGetPathOfFile(){
		$this->pathGenerator
			->expects(self::once())
			->method('generatePath')
			->with(self::UUID);

		$this->localMount->getMetadata(self::UUID);
	}

	public function test_path_getMetadata_shouldDeleteFile(){
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

		$this->fileSystem
			->expects(self::once())
			->method('getMetadata')
			->with(self::A_PATH);

		$this->localMount->getMetadata(self::UUID);
	}

	public function test_Path_getMetadata_shouldReturnMetadata(){

		$this->fileSystem->method('getMetadata')->willReturn(['timestamp'=>0,'size'=>0]);

		$metadata = $this->localMount->getMetadata(self::UUID);

		self::assertInstanceOf(Metadata::class,$metadata);
	}

	public function test_Metadata_getMetadata_shouldGetMimeType(){
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);
		$this->fileSystem->method('getMetadata')->willReturn(['timestamp'=>0,'size'=>0]);

		$this->fileSystem
			->expects(self::once())
			->method('getMimeType')
			->with(self::A_PATH);

		$this->localMount->getMetadata(self::UUID);
	}

	public function test_FileNotWritten_getMetadata_shouldReturnFalse(){

		$this->fileSystem->method('getMetadata')->willReturn(false);

		$metadata = $this->localMount->getMetadata(self::UUID);

		$this->assertFalse($metadata);
	}

	public function test_uuid_getSignedUrl_shouldReturnPresignedUrl(){
		$actualSignedUrl = $this->localMount->getSignedUrl(self::UUID);

		self::assertEquals(\Kronos\FileSystem\Mount\Local\Local::SIGNED_URL_BASE_PATH.self::UUID,$actualSignedUrl);
	}
}