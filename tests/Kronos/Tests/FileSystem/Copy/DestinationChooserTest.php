<?php

namespace Kronos\Tests\FileSystem\Copy;

use Kronos\FileSystem\Copy\DestinationChooser;
use Kronos\FileSystem\Mount\MountInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DestinationChooserTest extends TestCase
{
    private const SOURCE_MOUNT_TYPE = 'source mount type';
    private const IMPORTATION_MOUNT_TYPE = 'importation mount type';

    /**
     * @var MountInterface & MockObject
     */
    private $sourceMount;

    /**
     * @var MountInterface & MockObject
     */
    private $importationMount;

    /**
     * @var DestinationChooser
     */
    private $chooser;

    public function setUp(): void
    {
        $this->sourceMount = $this->createMock(MountInterface::class);
        $this->importationMount = $this->createMock(MountInterface::class);
    }

    public function test_sourceMount_getSourceMount_shouldReturnSourceMount(): void
    {
        $this->buildChooserWithDifferentSourceAndImportationMounts();

        $actualSourceMount = $this->chooser->getSourceMount();

        self::assertSame($this->sourceMount, $actualSourceMount);
    }

    public function test_sourceMountSelfContained_getDestinationMount_shouldReturnSourceMount(): void
    {
        $this->buildChooserWithDifferentSourceAndImportationMounts();
        $this->sourceMount
            ->expects(self::once())
            ->method('isSelfContained')
            ->willReturn(true);

        $actualDestinationMount = $this->chooser->getDestinationMount();

        self::assertSame($this->sourceMount, $actualDestinationMount);
    }

    public function test_sourceMountNotSelfContained_getDestinationMount_shouldReturnSourceMount(): void
    {
        $this->buildChooserWithDifferentSourceAndImportationMounts();
        $this->sourceMount
            ->expects(self::once())
            ->method('isSelfContained')
            ->willReturn(false);

        $actualDestinationMount = $this->chooser->getDestinationMount();

        self::assertSame($this->importationMount, $actualDestinationMount);
    }

    public function test_sourceMountSelfContained_getDestinationMountType_shouldReturnSourceMount(): void
    {
        $this->buildChooserWithDifferentSourceAndImportationMounts();
        $this->sourceMount
            ->expects(self::once())
            ->method('isSelfContained')
            ->willReturn(true);

        $actualDestinationMountType = $this->chooser->getDestinationMountType();

        self::assertSame(self::SOURCE_MOUNT_TYPE, $actualDestinationMountType);
    }

    public function test_sourceMountNotSelfContained_getDestinationMountType_shouldReturnSourceMount(): void
    {
        $this->buildChooserWithDifferentSourceAndImportationMounts();
        $this->sourceMount
            ->expects(self::once())
            ->method('isSelfContained')
            ->willReturn(false);

        $actualDestinationMountType = $this->chooser->getDestinationMountType();

        self::assertSame(self::IMPORTATION_MOUNT_TYPE, $actualDestinationMountType);
    }

    public function test_differenceSourceAndImportTypes_canUseCopy_shouldReturnFalse(): void
    {
        $this->buildChooserWithDifferentSourceAndImportationMounts();

        $canUseCopy = $this->chooser->canUseCopy();

        self::assertFalse($canUseCopy);
    }

    public function test_sameSourceAndImportTypes_canUseCopy_shouldReturnTrue(): void
    {
        $this->chooser = new DestinationChooser(
            $this->sourceMount,
            self::SOURCE_MOUNT_TYPE,
            $this->sourceMount,
            self::SOURCE_MOUNT_TYPE
        );

        $canUseCopy = $this->chooser->canUseCopy();

        self::assertTrue($canUseCopy);
    }

    protected function buildChooserWithDifferentSourceAndImportationMounts(): void
    {
        $this->chooser = new DestinationChooser(
            $this->sourceMount,
            self::SOURCE_MOUNT_TYPE,
            $this->importationMount,
            self::IMPORTATION_MOUNT_TYPE
        );
    }
}
