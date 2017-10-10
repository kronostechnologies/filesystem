<?php
namespace Kronos\Tests\FileSystem\Mount\Local;

use Kronos\FileSystem\File\Internal\Metadata;
use Kronos\FileSystem\Mount\PathGeneratorInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\File;
use League\Flysystem\Filesystem;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class LocalTest extends PHPUnit_Framework_TestCase{

	const UUID = 'UUID';
	const A_PATH = 'A_PATH';
	const A_FILE_NAME = 'A_FILE_NAME';
	const A_FILE_PATH = 'A_FILE_PATH';
	const A_LOCATION = 'A_LOCATION';
	const AN_URI = 'AN_URI';
	const BASE_URL = '/base/url?id=';

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

		$this->localMount = new localMountTestable($this->pathGenerator,$this->fileSystem);
	}

	public function test_uuid_get_shouldGetPathOfFile(){
		$this->fileSystem->method('get')->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(File::class));
		$this->pathGenerator
			->expects(self::once())
			->method('generatePath')
			->with(self::UUID);

		$this->localMount->get(self::UUID, self::A_FILE_NAME);
	}

	public function test_path_get_shouldReadStream(){
		$this->fileSystem->method('get')->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(File::class));
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

		$this->fileSystem
			->expects(self::once())
			->method('get')
			->with(self::A_PATH);

		$this->localMount->get(self::UUID, self::A_FILE_NAME);
	}

	public function test_stream_get_shouldReturnStream(){
		$this->fileSystem->method('get')->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(File::class));

		$file = $this->localMount->get(self::UUID, self::A_FILE_NAME);

		$this->assertInstanceOf(\Kronos\FileSystem\File\File::class,$file);
	}

	public function test_uuid_delete_shouldGetPathOfFile(){
		$this->pathGenerator
			->expects(self::once())
			->method('generatePath')
			->with(self::UUID);

		$this->localMount->delete(self::UUID, self::A_FILE_NAME);
	}

	public function test_path_delete_shouldDeleteFile(){
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

		$this->fileSystem
			->expects(self::once())
			->method('delete')
			->with(self::A_PATH);

		$this->localMount->delete(self::UUID, self::A_FILE_NAME);
	}

	public function test_FileDeleted_delete_shouldReturnTrue(){

		$this->fileSystem->method('delete')->willReturn(true);

		$deleted = $this->localMount->delete(self::UUID, self::A_FILE_NAME);

		$this->assertTrue($deleted);
	}

	public function test_FileNotDeleted_delete_shouldReturnFalse(){

		$this->fileSystem->method('delete')->willReturn(false);

		$deleted = $this->localMount->delete(self::UUID, self::A_FILE_NAME);

		$this->assertFalse($deleted);
	}

	public function test_uuid_put_shouldGetPathOfFile(){
		$this->pathGenerator
			->expects(self::once())
			->method('generatePath')
			->with(self::UUID);

		$this->localMount->put(self::UUID,self::A_FILE_PATH, self::A_FILE_NAME);
	}

	public function test_path_put_shouldPut(){
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

		$this->fileSystem
			->expects(self::once())
			->method('put')
			->with(self::A_PATH,localMountTestable::A_FILE_CONTENT);

		$this->localMount->put(self::UUID,self::A_FILE_PATH, self::A_FILE_NAME);
	}

	public function test_FileWritten_put_shouldReturnTrue(){

		$this->fileSystem->method('put')->willReturn(true);

		$written = $this->localMount->put(self::UUID,self::A_FILE_PATH, self::A_FILE_NAME);

		$this->assertTrue($written);
	}

	public function test_FileNotWritten_put_shouldReturnFalse(){

		$this->fileSystem->method('put')->willReturn(false);

		$written = $this->localMount->put(self::UUID,self::A_FILE_PATH, self::A_FILE_NAME);

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

		$this->localMount->getMetadata(self::UUID, self::A_FILE_NAME);
	}

	public function test_path_getMetadata_shouldDeleteFile(){
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

		$this->fileSystem
			->expects(self::once())
			->method('getMetadata')
			->with(self::A_PATH);

		$this->localMount->getMetadata(self::UUID, self::A_FILE_NAME);
	}

	public function test_Path_getMetadata_shouldReturnMetadata(){

		$this->fileSystem->method('getMetadata')->willReturn(['timestamp'=>0,'size'=>0]);

		$metadata = $this->localMount->getMetadata(self::UUID, self::A_FILE_NAME);

		self::assertInstanceOf(Metadata::class,$metadata);
	}

	public function test_Metadata_getMetadata_shouldGetMimeType(){
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);
		$this->fileSystem->method('getMetadata')->willReturn(['timestamp'=>0,'size'=>0]);

		$this->fileSystem
			->expects(self::once())
			->method('getMimeType')
			->with(self::A_PATH);

		$this->localMount->getMetadata(self::UUID, self::A_FILE_NAME);
	}

	public function test_FileNotWritten_getMetadata_shouldReturnFalse(){

		$this->fileSystem->method('getMetadata')->willReturn(false);

		$metadata = $this->localMount->getMetadata(self::UUID, self::A_FILE_NAME);

		$this->assertFalse($metadata);
	}

	public function test_BaseUrl_getSignedUrl_shouldReturnPresignedUrl(){
		$this->localMount->setBaseUrl(self::BASE_URL);

		$actualUrl = $this->localMount->getUrl(self::UUID, self::A_FILE_NAME);

		self::assertEquals(self::BASE_URL.self::UUID, $actualUrl);
	}

	public function test_NoBaseUrl_getSignedUrl_shouldThrowException() {
		$this->expectException(\Exception::class);

		$this->localMount->getUrl(self::UUID, self::A_FILE_NAME);
	}
}


class localMountTestable extends \Kronos\FileSystem\Mount\Local\Local {

	const A_FILE_CONTENT = 'A_FILE_CONTENT';
	protected function getFileContent($path) {
		return self::A_FILE_CONTENT;
	}

}