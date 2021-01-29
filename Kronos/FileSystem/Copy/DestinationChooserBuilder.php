<?php

namespace Kronos\FileSystem\Copy;

use Kronos\FileSystem\FileRepositoryInterface;
use Kronos\FileSystem\Mount\Selector;

class DestinationChooserBuilder
{
    /**
     * @var FileRepositoryInterface
     */
    private $fileRepository;

    /**
     * @var Selector
     */
    private $mountSelector;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * CopyDestinationChooser constructor.
     * @param FileRepositoryInterface $fileRepository
     * @param Selector $mountSelector
     * @param Factory|null $factory
     */
    public function __construct(FileRepositoryInterface $fileRepository, Selector $mountSelector, Factory $factory = null)
    {
        $this->fileRepository = $fileRepository;
        $this->mountSelector = $mountSelector;
        $this->factory = $factory ?? new Factory();
    }

    public function getChooserForFileId($id): DestinationChooser
    {
        $sourceMountType = $this->fileRepository->getFileMountType($id);
        $sourceMount = $this->mountSelector->selectMount($sourceMountType);

        $importationMountType = $this->mountSelector->getImportationMountType();
        $importationMount = $this->mountSelector->selectMount($importationMountType);

         return $this->factory->createDestinationChooser(
             $sourceMount,
             $sourceMountType,
             $importationMount,
             $importationMountType
         );
    }
}
