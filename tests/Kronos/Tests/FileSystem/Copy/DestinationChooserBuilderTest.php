<?php

namespace Kronos\Tests\FileSystem\Copy;

use Kronos\FileSystem\Copy\DestinationChooser;
use Kronos\FileSystem\Copy\DestinationChooserBuilder;
use Kronos\FileSystem\Copy\Factory;
use Kronos\FileSystem\FileRepositoryInterface;
use Kronos\FileSystem\Mount\MountInterface;
use Kronos\FileSystem\Mount\Selector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DestinationChooserBuilderTest extends TestCase
{
    const FILE_ID = 'file id';
    const SOURCE_MOUNT_TYPE = 'source mount';
    const IMPORTATION_MOUNT_TYPE = 'importation mount';
    /**
     * @var FileRepositoryInterface | MockObject
     */
    private $fileRepository;

    /**
     * @var Selector | MockObject
     */
    private $selector;

    /**
     * @var Factory | MockObject
     */
    private $factory;

    /**
     * @var MountInterface | MockObject
     */
    private $sourceMount;

    /**
     * @var MountInterface | MockObject
     */
    private $importationMount;

    /**
     * @var DestinationChooserTest
     */
    private $builder;

    public function setUp(): void
    {
        $this->fileRepository = $this->createMock(FileRepositoryInterface::class);
        $this->selector = $this->createMock(Selector::class);
        $this->factory = $this->createMock(Factory::class);

        $this->setUpMountTypes();
        $this->setUpSelectorAndMounts();

        $this->builder = new DestinationChooserBuilder(
            $this->fileRepository,
            $this->selector,
            $this->factory
        );
    }

    public function test_id_getChooserForFileId_shouldGetFileMountType(): void
    {
        $this->fileRepository
            ->expects(self::once())
            ->method('getFileMountType')
            ->with(self::FILE_ID);

        $this->builder->getChooserForFileId(self::FILE_ID);
    }

    public function test_fileMountType_getChooserForFileId_shouldSelectMount(): void
    {
        $this->selector
            ->expects(self::at(0))
            ->method('selectMount')
            ->with(self::SOURCE_MOUNT_TYPE);

        $this->builder->getChooserForFileId(self::FILE_ID);
    }

    public function test_mountSelector_getChooserForFileId_shouldGetImportationMountType(): void
    {
        $this->selector
            ->expects(self::at(1))
            ->method('getImportationMountType');

        $this->builder->getChooserForFileId(self::FILE_ID);
    }

    public function test_importationMountType_getChooserForFileId_shouldGetSelectMount(): void
    {
        $this->selector
            ->expects(self::at(2))
            ->method('selectMount')
            ->with(self::IMPORTATION_MOUNT_TYPE);

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
