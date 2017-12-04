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
	const IMPORTATION_MOUNT_TYPE = 'Importation mount Type';
	const NEW_FILE_UUID = 'newFileUuid';
	const SOURCE_MOUNT_TYPE = 'Source mount type';
	const PUT_STREAM_RESULT = self::HAS_FILE;
	const HAS_FILE = true;

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
	 * @var MountInterface|PHPUnit_Framework_MockObject_MockObject
	 */
	private $sourceMount;

	/**
	 * @var MountInterface|PHPUnit_Framework_MockObject_MockObject
	 */
	private $importationMount;

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
		$this->mount->method('put')->willReturn(self::PUT_STREAM_RESULT);

		$this->mountSelector
			->expects(self::once())
			->method('getImportationMount')
			->willReturn($this->mount);

		$this->fileSystem->put(self::A_FILE_PATH,self::FILE_NAME);
	}

	public function test_mount_put_shouldAddNewFile(){
		$this->mount->method('put')->willReturn(self::PUT_STREAM_RESULT);
		$this->mount->method('getMountType')->willReturn(self::MOUNT_TYPE);
		$this->mountSelector->method('getImportationMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::once())
			->method('addNewFile')
			->with(self::MOUNT_TYPE,self::FILE_NAME);

		$this->fileSystem->put(self::A_FILE_PATH,self::FILE_NAME);
	}

	public function test_mountAndUuid_put_putFile(){
		$this->mount->method('put')->willReturn(self::PUT_STREAM_RESULT);
		$this->mountSelector->method('getImportationMount')->willReturn($this->mount);
		$this->fileRepository->method('addNewFile')->willReturn(self::UUID);

		$this->mount
			->expects(self::once())
			->method('put')
			->with(self::UUID,self::A_FILE_PATH);

		$this->fileSystem->put(self::A_FILE_PATH,self::FILE_NAME);
	}

	public function test_fileAsBeenWritten_put_shouldReturnFileUuid(){
		$this->mount->method('put')->willReturn(self::PUT_STREAM_RESULT);
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

		$this->fileSystem->getUrl(self::UUID);
	}

	public function test_mountType_getUrl_shouldSelectMount(){
		$this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

		$this->mountSelector
			->expects(self::once())
			->method('selectMount')
			->with(self::MOUNT_TYPE)
			->willReturn($this->mount);

		$this->fileSystem->getUrl(self::UUID);
	}

	public function test_mountSelected_getUrl_shouldGetUrl(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount
			->expects(self::once())
			->method('getUrl')
			->with(self::UUID);

		$this->fileSystem->getUrl(self::UUID);
	}

	public function test_mountCouldNotHaveBeenSelected_getUrl_shouldThrowMountNotFoundException(){

		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(MountNotFoundException::class);

		$this->fileSystem->getUrl(self::UUID);
	}

	public function test_signedUlr_getUrl_shouldReturnSignedUlr(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount->method('getUrl')->willReturn(self::A_SIGNED_URL);

		$actualSignedUrl = $this->fileSystem->getUrl(self::UUID);

		self::assertSame(self::A_SIGNED_URL,$actualSignedUrl);
	}

	public function test_givenId_delete_shouldGetFileName() {
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->fileRepository
			->expects(self::once())
			->method('getFileName')
			->with(self::UUID);

		$this->fileSystem->delete(self::UUID);
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

	public function test_mountCouldNotHaveBeenSelected_delete_shouldThrowMountNotFoundException(){

		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(MountNotFoundException::class);

		$this->fileSystem->delete(self::UUID);
	}

	public function test_mountSelected_delete_ShouldDelteInMount(){
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->givenFileName();

		$this->mount
			->expects(self::once())
			->method('delete')
			->with(self::UUID, self::FILE_NAME);

		$this->fileSystem->delete(self::UUID);
	}

	public function test_DeleteInMount_delete_ShouldDeleteInRepository() {
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mount
			->method('delete')
			->willReturn(self::HAS_FILE);
		$this->fileRepository
			->expects(self::once())
			->method('delete')
			->with(self::UUID);

		$this->fileSystem->delete(self::UUID, self::FILE_NAME);
	}

	public function test_DeleteFailed_delete_ShouldNotDeleteInRepository() {
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mount
			->method('delete')
			->willReturn(false);
		$this->fileRepository
			->expects(self::never())
			->method('delete');

		$this->fileSystem->delete(self::UUID, self::FILE_NAME);
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

	public function test_mountCouldNotHaveBeenSelected_retrieve_shouldThrowMountNotFoundException(){
		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(MountNotFoundException::class);

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
		$this->metadata = new Metadata();
		$this->mount->method('getMetadata')->willReturn($this->metadata);
		$this->file = $this->getMockWithoutInvokingTheOriginalConstructor(File::class);
		$this->mount->method('get')->willReturn($this->file);
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::once())
			->method('getFileMountType')
			->with(self::UUID);

		$this->fileSystem->getMetadata(self::UUID);
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

		$this->fileSystem->getMetadata(self::UUID);
	}

	public function test_mountCouldNotHaveBeenSelected_getMetadata_shouldThrowMountNotFoundException(){
		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(MountNotFoundException::class);

		$this->fileSystem->getMetadata(self::UUID);
	}

	public function test_mount_getMetadata_getMetadata(){
		$this->metadata = new Metadata();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mount->method('getMetadata')->willReturn($this->metadata);

		$this->mount
			->expects(self::once())
			->method('getMetadata')
			->with(self::UUID);

		$this->fileSystem->getMetadata(self::UUID);
	}

	public function test_metadata_getMetadata_ShouldGetFileName(){
		$this->metadata = new Metadata();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mount->method('getMetadata')->willReturn($this->metadata);

		$this->fileRepository
			->expects(self::once())
			->method('getFileName')
			->with(self::UUID);

		$this->fileSystem->getMetadata(self::UUID);
	}

	public function test_InternalMetadata_getMetadata_ShouldTranslateMetadata(){
		$this->metadata = new Metadata();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mount->method('getMetadata')->willReturn($this->metadata);

		$this->metadataTranslator
			->expects(self::once())
			->method('translateInternalToExposed')
			->with($this->metadata);

		$this->fileSystem->getMetadata(self::UUID);
	}

	public function test_mount_getMetadata_ShouldReturnMetadata(){
		$this->metadata = new Metadata();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mount->method('getMetadata')->willReturn($this->metadata);

		$actualMetadata = $this->fileSystem->getMetadata(self::UUID);

		self::assertSame($actualMetadata,$actualMetadata);
	}


	public function test_givenId_get_shouldMountAssociatedWithId(){
		$this->givenWillReturnFile();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->fileRepository
			->expects(self::exactly(2))
			->method('getFileMountType')
			->with(self::UUID);

		$this->fileSystem->get(self::UUID);
	}

	public function test_mountType_get_shouldSelectMount(){
		$this->givenWillReturnFile();
		$this->fileRepository->method('getFileMountType')->willReturn(self::MOUNT_TYPE);

		$this->mountSelector
			->expects(self::exactly(2))
			->method('selectMount')
			->with(self::MOUNT_TYPE)
			->willReturn($this->mount);

		$this->fileSystem->get(self::UUID);
	}

	public function test_mountCouldNotHaveBeenSelected_get_shouldThrowMountNotFoundException(){
		$this->givenWillReturnFile();
		$this->mountSelector->method('selectMount')->willReturn(null);

		$this->expectException(MountNotFoundException::class);

		$this->fileSystem->get(self::UUID);
	}

	public function test_mount_get_shouldGetFile(){
		$this->givenWillReturnFile();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount
			->expects(self::once())
			->method('get')
			->with(self::UUID);

		$this->fileSystem->get(self::UUID);
	}

	public function test_mount_get_shouldGetMetadata(){
		$this->givenWillReturnFile();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->mount
			->expects(self::once())
			->method('getMetadata')
			->with(self::UUID);

		$this->fileSystem->get(self::UUID);
	}

	public function test_File_get_shouldReturnFile(){
		$this->givenWillReturnFile();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$this->file = $this->fileSystem->get(self::UUID);

		self::assertInstanceOf(File::class,$this->file);
	}

	public function test_Metadata_get_shouldBeTheMetadataInFileObject(){
		$this->metadataTranslator->method('translateInternalToExposed')->willReturn($this->getMockWithoutInvokingTheOriginalConstructor(\Kronos\FileSystem\File\Metadata::class));
		$this->givenWillReturnFile();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$file = $this->fileSystem->get(self::UUID);

		self::assertInstanceOf(\Kronos\FileSystem\File\Metadata::class,$file->metadata);
	}

	public function test_copy_shouldGetFileMountType() {
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->fileRepository
			->expects(self::once())
			->method('getFileMountType')
			->with(self::UUID);

		$this->fileSystem->copy(self::UUID);
	}

	public function test_copy_shouldGetFileName() {
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->fileRepository
			->expects(self::once())
			->method('getFileName')
			->with(self::UUID);

		$this->fileSystem->copy(self::UUID);
	}

	public function test_ImportationMountType_copy_shouldSelectImportationMount() {
		$this->givenImporationMountType();
		$this->givenFileName();
		$this->givenFileInImportationMount();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mountSelector
			->expects(self::once())
			->method('selectMount')
			->with(self::IMPORTATION_MOUNT_TYPE);

		$this->fileSystem->copy(self::UUID);
	}

	public function test_ImportationMountTypeAndFileName_copy_shouldAddNewFile() {
		$this->givenImporationMountType();
		$this->givenFileName();
		$this->givenFileInImportationMount();
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->fileRepository
			->expects(self::once())
			->method('addNewFile')
			->with(self::IMPORTATION_MOUNT_TYPE, self::FILE_NAME);

		$this->fileSystem->copy(self::UUID);
	}

	public function test_AddedFileAndSameSourceAndImporationMountTypes_copy_shouldCopyFile() {
		$this->givenImporationMountType();
		$this->givenFileName();
		$this->givenFileInImportationMount();
		$this->fileRepository->method('addNewFile')->willReturn(self::NEW_FILE_UUID);
		$this->mountSelector->method('selectMount')->willReturn($this->mount);
		$this->mount
			->expects(self::once())
			->method('copy')
			->with(self::UUID, self::NEW_FILE_UUID, self::FILE_NAME);

		$this->fileSystem->copy(self::UUID);
	}

	public function test_FileCopied_copy_shouldReturnNewUuid() {
		$this->givenImporationMountType();
		$this->givenFileInImportationMount();
		$this->fileRepository->method('addNewFile')->willReturn(self::NEW_FILE_UUID);
		$this->mountSelector->method('selectMount')->willReturn($this->mount);

		$actualUuid = $this->fileSystem->copy(self::UUID);

		$this->assertSame(self::NEW_FILE_UUID, $actualUuid);
	}

	public function test_DifferentSourceAndImportationMountTypes_copy_shouldNotCallCopy() {
		$this->givenImporationMountType();
		$this->givenDifferentSourceMount();
		$this->givenSourceAndImporationMounts();
		$this->importationMount
			->expects(self::never())
			->method('copy');

		$this->fileSystem->copy(self::UUID);
	}

	public function test_DifferentSourceAndImportationMountTypes_copy_shouldSelectSourceMount() {
		$this->givenImporationMountType();
		$this->givenDifferentSourceMount();
		$this->givenSourceAndImporationMounts();
		$this->mountSelector
			->expects(self::at(1))
			->method('selectMount')
			->with(self::IMPORTATION_MOUNT_TYPE);
		$this->mountSelector
			->expects(self::at(2))
			->method('selectMount')
			->with(self::SOURCE_MOUNT_TYPE);

		$this->fileSystem->copy(self::UUID);
	}

	public function test_SourceMount_copy_shouldGetFile() {
		$this->givenImporationMountType();
		$this->givenDifferentSourceMount();
		$this->givenSourceAndImporationMounts();
		$this->sourceMount
			->expects(self::once())
			->method('get')
			->with(self::UUID);

		$this->fileSystem->copy(self::UUID);
	}

	public function test_File_copy_shouldReadStream() {
		$this->givenImporationMountType();
		$this->givenDifferentSourceMount();
		$this->givenSourceAndImporationMounts();
		$this->file
			->expects(self::once())
			->method('readStream');

		$this->fileSystem->copy(self::UUID);
	}

	public function test_Stream_copy_shouldPutStream() {
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

	public function test_StreamPut_copy_shouldReturnNewFileUuid() {
		$this->givenImporationMountType();
		$this->givenDifferentSourceMount();
		$this->fileRepository->method('addNewFile')->willReturn(self::NEW_FILE_UUID);
		$this->givenSourceAndImporationMounts();
		$stream = tmpfile();
		$this->file->method('readStream')->willReturn($stream);

		$actualUuid = $this->fileSystem->copy(self::UUID);

		$this->assertEquals(self::NEW_FILE_UUID, $actualUuid);
	}

	public function test_Uuid_has_shouldGetFileMountType() {
		$this->fileRepository
			->expects(self::once())
			->method('getFileMountType')
			->with(self::UUID);
		$this->mountSelector
			->method('selectMount')
			->willReturn($this->mount);

		$this->fileSystem->has(self::UUID);
	}

	public function test_Uuid_has_shouldGetFileName() {
		$this->fileRepository
			->expects(self::once())
			->method('getFileName')
			->with(self::UUID);
		$this->mountSelector
			->method('selectMount')
			->willReturn($this->mount);

		$this->fileSystem->has(self::UUID);
	}

	public function test_MountType_has_shouldSelectMount() {
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

	public function test_Mount_has_shouldCheckAndReturnIfMountHasUuid() {
		$this->mountSelector
			->method('selectMount')
			->willReturn($this->mount);
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

	private function givenWillReturnFile(){
		$this->metadata = new Metadata();
		$this->mount->method('getMetadata')->willReturn($this->metadata);
		$this->file = $this->getMockWithoutInvokingTheOriginalConstructor(File::class);
		$this->mount->method('get')->willReturn($this->file);
	}

	protected function givenImporationMountType() {
		$this->mountSelector->method('getImportationMountType')->willReturn(self::IMPORTATION_MOUNT_TYPE);
	}

	protected function givenFileName() {
		$this->fileRepository->method('getFileName')->willReturn(self::FILE_NAME);
	}

	protected function givenFileInImportationMount() {
		$this->fileRepository->method('getFileMountType')->willReturn(self::IMPORTATION_MOUNT_TYPE);
	}

	protected function givenDifferentSourceMount() {
		$this->fileRepository->method('getFileMountType')->willReturn(self::SOURCE_MOUNT_TYPE);
	}

	protected function givenSourceAndImporationMounts() {
		$this->file = $this->getMockWithoutInvokingTheOriginalConstructor(File::class);
		$this->sourceMount = $this->getMock(MountInterface::class);
		$this->sourceMount->method('get')->willReturn($this->file);
		$this->importationMount = $this->getMock(MountInterface::class);
		$this->mountSelector->method('selectMount')->willReturnMap([[self::SOURCE_MOUNT_TYPE, $this->sourceMount], [self::IMPORTATION_MOUNT_TYPE, $this->importationMount]]);
	}
}