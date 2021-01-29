<?php

namespace Kronos\FileSystem\Copy;

use Kronos\FileSystem\Mount\MountInterface;

class Factory
{
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
