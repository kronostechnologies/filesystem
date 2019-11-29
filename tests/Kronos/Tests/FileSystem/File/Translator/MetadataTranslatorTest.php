<?php

namespace Kronos\Tests\FileSystem\File\Translator;

use DateTime;
use Kronos\FileSystem\File\Internal\Metadata;
use Kronos\FileSystem\File\Translator\MetadataTranslator;
use PHPUnit\Framework\TestCase;

class MetadataTranslatorTest extends TestCase
{
    const FILE_SIZE = 12;
    const FILE_MIMETYPE = 'a_file_type';
    const FILE_NAME = 'a_file_name';

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var MetadataTranslator
     */
    private $translator;

    public function setUp(): void
    {
        $this->givenInternalMetadata();
        $this->translator = new MetadataTranslator();
    }

    public function test_internalMetadata_translateInternalToExposed_ShouldReturnMetadata()
    {
        $actualMetadta = $this->translator->translateInternalToExposed($this->metadata);

        self::assertInstanceOf(\Kronos\FileSystem\File\Metadata::class, $actualMetadta);
    }

    public function test_internalMetadataSize_translateInternalToExposed_MetadataSizeShouldBeTheSame()
    {
        $actualMetadta = $this->translator->translateInternalToExposed($this->metadata);

        self::assertSame($this->metadata->size, $actualMetadta->getSize());
    }

    public function test_internalMetadataMimetype_translateInternalToExposed_MetadataMimeTypeShouldBeTheSame()
    {
        $actualMetadta = $this->translator->translateInternalToExposed($this->metadata);

        self::assertSame($this->metadata->mimetype, $actualMetadta->getMimetype());
    }

    public function test_internalMetadataName_translateInternalToExposed_MetadataNameShouldBeTheSame()
    {
        $actualMetadta = $this->translator->translateInternalToExposed($this->metadata);

        self::assertSame($this->metadata->name, $actualMetadta->getName());
    }

    public function test_internalMetadataLastModifiedDate_translateInternalToExposed_MetadataLastModifiedDateShouldBeTheSame(
    )
    {
        $actualMetadta = $this->translator->translateInternalToExposed($this->metadata);

        self::assertSame($this->metadata->lastModifiedDate, $actualMetadta->getLastModifiedDate());
    }


    private function givenInternalMetadata()
    {
        $this->metadata = new Metadata();
        $this->metadata->size = self::FILE_SIZE;
        $this->metadata->lastModifiedDate = $this->createMock(DateTime::class);
        $this->metadata->mimetype = self::FILE_MIMETYPE;
        $this->metadata->name = self::FILE_NAME;
    }
}
