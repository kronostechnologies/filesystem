<?php
namespace Kronos\Tests\FileSystem\Mount\S3;

use Aws\CommandInterface;
use Aws\S3\S3Client;
use Kronos\FileSystem\Mount\PathGeneratorInterface;
use Kronos\FileSystem\Mount\S3\S3;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;

class S3Test extends PHPUnit_Framework_TestCase{

	const UUID = 'UUID';
	const A_PATH = 'A_PATH';
	const A_STREAM = 'A_STREAM';
	const A_LOCATION = 'A_LOCATION';
	const S3_BUCKET = 'S3_BUCKET';
	const AN_URI = 'AN_URI';
	const A_RESOURCE = 'A_RESOURCE';

	/**
	 * @var PathGeneratorInterface|PHPUnit_Framework_MockObject_MockObject
	 */
	private $pathGenerator;

	/**
	 * @var AwsS3Adapter|PHPUnit_Framework_MockObject_MockObject
	 */
	private $s3Adaptor;

	/**
	 * @var FileSystem|PHPUnit_Framework_MockObject_MockObject
	 */
	private $fileSystem;

	/**
	 * @var S3Client|PHPUnit_Framework_MockObject_MockObject
	 */
	private $s3Client;

	/**
	 * @var S3
	 */
	private $s3mount;

	public function setUp(){
		$this->fileSystem = $this->getMockWithoutInvokingTheOriginalConstructor(Filesystem::class);
		$this->s3Client = $this->getMockWithoutInvokingTheOriginalConstructor(S3Client::class);
		$this->s3Adaptor = $this->getMockWithoutInvokingTheOriginalConstructor(AwsS3Adapter::class);
		$this->pathGenerator = $this->getMockWithoutInvokingTheOriginalConstructor(PathGeneratorInterface::class);

		$this->fileSystem->method('getAdapter')->willReturn($this->s3Adaptor);

		$this->s3mount = new S3($this->pathGenerator,$this->fileSystem);
	}

	public function test_uuid_getResource_shouldGetPathOfFile(){
		$this->pathGenerator
			->expects(self::once())
			->method('generatePath')
			->with(self::UUID);

		$this->s3mount->getResource(self::UUID);
	}

	public function test_path_getResource_shouldReadStream(){
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

		$this->fileSystem
			->expects(self::once())
			->method('readStream')
			->with(self::A_PATH);

		$this->s3mount->getResource(self::UUID);
	}

	public function test_noStream_getResource_shouldReturnEmptyString(){
		$this->fileSystem->method('readStream')->willReturn(false);

		$stream = $this->s3mount->getResource(self::UUID);

		$this->assertFalse($stream);
	}

	public function test_stream_getResource_shouldReturnStream(){
		$this->fileSystem->method('readStream')->willReturn(self::A_STREAM);

		$stream = $this->s3mount->getResource(self::UUID);

		$this->assertEquals(self::A_STREAM,$stream);
	}

	public function test_getSignedUrl_shouldGetS3Client(){
		$this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
		$this->s3Client->method('getCommand')->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(CommandInterface::class));
		$this->s3Client->method('createPresignedRequest')->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(RequestInterface::class));

		$this->s3Adaptor
			->expects(self::once())
			->method('getClient');

		$this->s3mount->getSignedUrl(self::UUID);
	}

	public function test_uuid_getSignedUrl_shouldGetPathOfFile(){
		$this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
		$this->s3Client->method('getCommand')->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(CommandInterface::class));
		$this->s3Client->method('createPresignedRequest')->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(RequestInterface::class));

		$this->pathGenerator
			->expects(self::once())
			->method('generatePath')
			->with(self::UUID);

		$this->s3mount->getSignedUrl(self::UUID);
	}

	public function test_path_getSignedUrl_shouldGetFileLocation(){
		$this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
		$this->s3Client->method('getCommand')->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(CommandInterface::class));
		$this->s3Client->method('createPresignedRequest')->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(RequestInterface::class));
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

		$this->s3Adaptor
			->expects(self::once())
			->method('applyPathPrefix')
			->with(self::A_PATH);

		$this->s3mount->getSignedUrl(self::UUID);
	}

	public function test_location_getSignedUrl_shouldGetCommandToGetFile(){
		$this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
		$this->s3Adaptor->method('applyPathPrefix')->willReturn(self::A_LOCATION);
		$this->s3Adaptor->method('getBucket')->willReturn(self::S3_BUCKET);
		$this->s3Client->method('createPresignedRequest')->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(RequestInterface::class));

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
			->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(CommandInterface::class));

		$this->s3mount->getSignedUrl(self::UUID);
	}

	public function test_command_getSignedUrl_shouldGetCommandToGetFile(){
		$command = $this->getMockWithoutInvokingTheOriginalConstructor(CommandInterface::class);
		$this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
		$this->s3Client->method('getCommand')->willReturn($command);

		$this->s3Client
			->expects(self::once())
			->method('createPresignedRequest')
			->with($command,S3::PRESIGNED_URL_LIFE_TIME)
			->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(RequestInterface::class));

		$this->s3mount->getSignedUrl(self::UUID);
	}

	public function test_request_getSignedUrl_shouldGetUriFromRequest(){
		$request = $this->getMockWithoutInvokingTheOriginalConstructor(RequestInterface::class);
		$this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
		$this->s3Client->method('getCommand')->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(CommandInterface::class));
		$this->s3Client->method('createPresignedRequest')->willReturn($request);

		$request
			->expects(self::once())
			->method('getUri');

		$this->s3mount->getSignedUrl(self::UUID);
	}

	public function test_uri_getSignedUrl_shouldReturnUri(){
		$request = $this->getMockWithoutInvokingTheOriginalConstructor(RequestInterface::class);
		$this->s3Adaptor->method('getClient')->willReturn($this->s3Client);
		$this->s3Client->method('getCommand')->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(CommandInterface::class));
		$this->s3Client->method('createPresignedRequest')->willReturn($request);
		$request->method('getUri')->willReturn(self::AN_URI);

		$actualUri = $this->s3mount->getSignedUrl(self::UUID);

		self::assertSame(self::AN_URI,$actualUri);
	}

	public function test_uuid_write_shouldGetPathOfFile(){
		$this->pathGenerator
			->expects(self::once())
			->method('generatePath')
			->with(self::UUID);

		$this->s3mount->write(self::UUID,self::A_RESOURCE);
	}

	public function test_path_write_shouldWrite(){
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

		$this->fileSystem
			->expects(self::once())
			->method('writeStream')
			->with(self::A_PATH,self::A_RESOURCE);

		$this->s3mount->write(self::UUID,self::A_RESOURCE);
	}

	public function test_FileWritten_write_shouldReturnTrue(){

		$this->fileSystem->method('writeStream')->willReturn(true);

		$written = $this->s3mount->write(self::UUID,self::A_RESOURCE);

		$this->assertTrue($written);
	}

	public function test_FileNotWritten_write_shouldReturnFalse(){

		$this->fileSystem->method('writeStream')->willReturn(false);

		$written = $this->s3mount->write(self::UUID,self::A_RESOURCE);

		$this->assertFalse($written);
	}

	public function test_getMountType_ShouldReutrnMountType(){
		$actualMountType = $this->s3mount->getMountType();

		self::assertEquals(S3::MOUNT_TYPE,$actualMountType);
	}

	public function test_uuid_delete_shouldGetPathOfFile(){
		$this->pathGenerator
			->expects(self::once())
			->method('generatePath')
			->with(self::UUID);

		$this->s3mount->delete(self::UUID);
	}

	public function test_path_delete_shouldDeleteFile(){
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

		$this->fileSystem
			->expects(self::once())
			->method('delete')
			->with(self::A_PATH);

		$this->s3mount->delete(self::UUID);
	}

	public function test_FileDeleted_delete_shouldReturnTrue(){

		$this->fileSystem->method('delete')->willReturn(true);

		$deleted = $this->s3mount->delete(self::UUID);

		$this->assertTrue($deleted);
	}

	public function test_FileNotDeleted_delete_shouldReturnFalse(){

		$this->fileSystem->method('delete')->willReturn(false);

		$deleted = $this->s3mount->delete(self::UUID);

		$this->assertFalse($deleted);
	}

	public function test_uuid_getMetadata_shouldGetPathOfFile(){
		$this->pathGenerator
			->expects(self::once())
			->method('generatePath')
			->with(self::UUID);

		$this->s3mount->getMetadata(self::UUID);
	}

	public function test_path_getMetadata_shouldDeleteFile(){
		$this->pathGenerator->method('generatePath')->willReturn(self::A_PATH);

		$this->fileSystem
			->expects(self::once())
			->method('getMetadata')
			->with(self::A_PATH);

		$this->s3mount->getMetadata(self::UUID);
	}

	public function test_Path_getMetadata_shouldReturnTrue(){

		$this->fileSystem->method('getMetadata')->willReturn([]);

		$metadata = $this->s3mount->getMetadata(self::UUID);

		$this->assertTrue(is_array($metadata));
	}

	public function test_FileNotWritten_delete_shouldReturnFalse(){

		$this->fileSystem->method('getMetadata')->willReturn(false);

		$metadata = $this->s3mount->getMetadata(self::UUID);

		$this->assertFalse($metadata);
	}
}