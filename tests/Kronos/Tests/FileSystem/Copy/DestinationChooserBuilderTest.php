<?php

namespace Kronos\Tests\FileSystem\Copy;

use Kronos\FileSystem\Copy\DestinationChooser;
use Kronos\FileSystem\Copy\DestinationChooserFactory;
use Kronos\FileSystem\Copy\Factory;
use Kronos\FileSystem\FileRepositoryInterface;
use Kronos\FileSystem\Mount\MountInterface;
use Kronos\FileSystem\Mount\Selector;
use Kronos\Tests\FileSystem\ExtendedTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DestinationChooserBuilderTest extends ExtendedTestCase
{
    const FILE_ID = 'file id';
    const SOURCE_MOUNT_TYPE = 'source mount';
    const IMPORTATION_MOUNT_TYPE = 'importation mount';

    private FileRepositoryInterface&MockObject $fileRepository;
    private Selector&MockObject $selector;
    private Factory&MockObject $factory;
    private MountInterface&MockObject $sourceMount;
    private MountInterface&MockObject $importationMount;
    private DestinationChooserFactory $builder;

    public function setUp(): void
    {
        $this->fileRepository = $this->createMock(FileRepositoryInterface::class);
        $this->selector = $this->createMock(Selector::class);
        $this->factory = $this->createMock(Factory::class);

        $this->setUpMountTypes();
        $this->setUpSelectorAndMounts();

        $this->builder = new DestinationChooserFactory(
            $this->fileRepository,
            $this->selector,
            $this->factory
        );
    }

    public function test_id_getChooserForFileId_shouldGetFileMountTypes(): void
    {
        $this->fileRepository
            ->expects(self::once())
            ->method('getFileMountType')
            ->with(self::FILE_ID);

        $this->builder->getChooserForFileId(self::FILE_ID);
    }

    public function test_importationMountType_getChooserForFileId_shouldGetSelectSourceAndImportMount(): void
    {
        $this->selector
            ->expects(self::exactly(2))
            ->method('selectMount')
            ->with(
                ...self::withConsecutive(
                    [self::SOURCE_MOUNT_TYPE],
                    [self::IMPORTATION_MOUNT_TYPE]
                )
            );

        $this->builder->getChooserForFileId(self::FILE_ID);
    }

    public function test_mountSelector_getChooserForFileId_shouldGetImportationMountType(): void
    {
        $this->selector
            ->expects(self::once())
            ->method('getImportationMountType');

        $this->builder->getChooserForFileId(self::FILE_ID);
    }

    public function test_sourceAndImportationMounts_getChooserForFileId_shouldCreateAndReturnChooser(): void
    {
        $expectedChooser = $this->createMock(DestinationChooser::class);
        $this->selector
            ->method('selectMount')
            ->willReturnOnConsecutiveCalls(
                $this->sourceMount,
                $this->importationMount
            );
        $this->factory
            ->expects(self::once())
            ->method('createDestinationChooser')
            ->with(
                $this->sourceMount,
                self::SOURCE_MOUNT_TYPE,
                $this->importationMount,
                self::IMPORTATION_MOUNT_TYPE
            )
            ->willReturn($expectedChooser);

        $actualChooser = $this->builder->getChooserForFileId(self::FILE_ID);

        self::assertSame($expectedChooser, $actualChooser);
    }

    protected function setUpSelectorAndMounts(): void
    {
        $this->sourceMount = $this->createMock(MountInterface::class);
        $this->importationMount = $this->createMock(MountInterface::class);
        $this->selector
            ->method('selectMount')
            ->willReturnMap([
                [self::SOURCE_MOUNT_TYPE, $this->sourceMount],
                [self::IMPORTATION_MOUNT_TYPE, $this->importationMount]
            ]);
    }

    protected function setUpMountTypes(): void
    {
        $this->fileRepository
            ->method('getFileMountType')
            ->willReturn(self::SOURCE_MOUNT_TYPE);
        $this->selector
            ->method('getImportationMountType')
            ->willReturn(self::IMPORTATION_MOUNT_TYPE);
    }
}
