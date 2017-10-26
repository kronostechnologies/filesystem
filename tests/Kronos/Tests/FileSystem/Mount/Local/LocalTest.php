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
	const A_FILE_CONTENT = 'File contents';
	const PUT_RESULT = self::COPY_RESULT;
	const SOURCE_UUID = 'source uuid';
	const TARGET_UUID = 'target uuid';
	const SOURCE_PATH = 'source path';
	const TARGET_PATH = 'target path';
	const COPY_RESULT = true;

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

		$this->fileSystem->method('delete')->willReturn(self::PUT_RESULT);

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
			->with(self::A_PATH,self::A_FILE_CONTENT);

		$this->localMount->put(self::UUID,self::A_FILE_PATH, self::A_FILE_NAME);
	}

	public function test_FileWritten_put_shouldReturnTrue(){

		$this->fileSystem->method('put')->willReturn(self::PUT_RESULT);

		$written = $this->localMount->put(self::UUID,self::A_FILE_PATH, self::A_FILE_NAME);

		$this->assertTrue($written);
	}

	public function test_FileNotWritten_put_shouldReturnFalse(){

		$this->fileSystem->method('put')->willReturn(false);

		$written = $this->localMount->put(self::UUID,self::A_FILE_PATH, self::A_FILE_NAME);

		$this->assertFalse($written);
	}

	public function test_putStream_shouldGetPathOfFile() {
		$this->pathGenerator
			->expects(self::once())
			->method('generatePath')
			->with(self::UUID);

		$this->localMount->putStream(self::UUID, self::A_FILE_CONTENT, self::A_FILE_NAME);
	}

	public function test_path_putStream_shouldPutAndReturnResult() {
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

	public function test_getMountType_ShouldReturnMountType(){
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

	public function test_copy_shouldGeneratePathForSourceUuid() {
		$this->pathGenerator
			->expects(self::at(0))
			->method('generatePath')
			->with(self::SOURCE_UUID, self::A_FILE_NAME);

		$this->localMount->copy(self::SOURCE_UUID, self::TARGET_UUID, self::A_FILE_NAME);
	}

	public function test_SourcePath_copy_shouldGeneratePathForTargetUuid() {
		$this->pathGenerator
			->expects(self::at(1))
			->method('generatePath')
			->with(self::TARGET_UUID, self::A_FILE_NAME);

		$this->localMount->copy(self::SOURCE_UUID, self::TARGET_UUID, self::A_FILE_NAME);
	}

	public function test_Paths_copy_shouldCopySourcePathToTargetPathAndReturnResult() {
		$this->pathGenerator->method('generatePath')->willReturnOnConsecutiveCalls(self::SOURCE_PATH, self::TARGET_PATH);
		$this->fileSystem
			->expects(self::once())
			->method('copy')
			->with(self::SOURCE_PATH, self::TARGET_PATH)
			->willReturn(self::COPY_RESULT);

		$actualResult = $this->localMount->copy(self::SOURCE_UUID, self::TARGET_UUID, self::A_FILE_NAME);

		$this->assertEquals(self::COPY_RESULT, $actualResult);
	}
}


class localMountTestable extends \Kronos\FileSystem\Mount\Local\Local {

	protected function getFileContent($path) {
		return LocalTest::A_FILE_CONTENT;
	}

}