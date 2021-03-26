<?php

namespace Kronos\FileSystem\Copy;

use Kronos\FileSystem\FileRepositoryInterface;
use Kronos\FileSystem\Mount\MountInterface;
use Kronos\FileSystem\Mount\Selector;

class Factory
{
    public function createDestinationChooserBuilder(FileRepositoryInterface $fileRepository, Selector $selector): DestinationChooserBuilder
    {
        return new DestinationChooserBuilder($fileRepository, $selector, $this);
    }

    public function createDestinationChooser(
        MountInterface $sourceMount,
        string $sourceMountType,
        MountInterface $importationMount,
        string $importationMountType
    ) : DestinationChooser
    {
        return new DestinationChooser($sourceMount, $sourceMountType, $importationMount, $importationMountType);
    }
}
