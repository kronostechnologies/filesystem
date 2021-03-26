<?php

namespace Kronos\FileSystem\Copy;

use Kronos\FileSystem\Mount\MountInterface;

class DestinationChooser
{
    /**
     * @var MountInterface
     */
    private $sourceMount;

    /**
     * @var string
     */
    private $sourceMountType;

    /**
     * @var MountInterface
     */
    private $importationMount;

    /**
     * @var string
     */
    private $importationMountType;

    /**
     * DestinationChooserBuilder constructor.
     * @param MountInterface $sourceMount
     * @param string $sourceMountType
     * @param MountInterface $importationMount
     * @param string $importationMountType
     */
    public function __construct(
        MountInterface $sourceMount,
        string $sourceMountType,
        MountInterface $importationMount,
        string $importationMountType
    ) {
        $this->sourceMount = $sourceMount;
        $this->sourceMountType = $sourceMountType;
        $this->importationMount = $importationMount;
        $this->importationMountType = $importationMountType;
    }


    public function getSourceMount(): MountInterface
    {
        return $this->sourceMount;
    }

    public function getDestinationMount(): MountInterface
    {
        if ($this->sourceMount->isSelfContained()) {
            return $this->sourceMount;
        } else {
            return $this->importationMount;
        }
    }

    public function getDestinationMountType(): string
    {
        if ($this->sourceMount->isSelfContained()) {
            return $this->sourceMountType;
        } else {
            return $this->importationMountType;
        }
    }

    public function canUseCopy(): bool
    {
        return $this->sourceMountType == $this->importationMountType;
    }
}
