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
        $actualMetadata = $this->translator->translateInternalToExposed($this->metadata);

        self::assertInstanceOf(\Kronos\FileSystem\File\Metadata::class, $actualMetadata);
    }

    public function test_internalMetadataSize_translateInternalToExposed_MetadataSizeShouldBeTheSame()
    {
        $actualMetadata = $this->translator->translateInternalToExposed($this->metadata);

        self::assertSame($this->metadata->size, $actualMetadata->getSize());
    }

    public function test_internalMetadataMimetype_translateInternalToExposed_MetadataMimeTypeShouldBeTheSame()
    {
        $actualMetadata = $this->translator->translateInternalToExposed($this->metadata);

        self::assertSame($this->metadata->mimetype, $actualMetadata->getMimetype());
    }

    public function test_internalMetadataName_translateInternalToExposed_MetadataNameShouldBeTheSame()
    {
        $actualMetadata = $this->translator->translateInternalToExposed($this->metadata);

        self::assertSame($this->metadata->name, $actualMetadata->getName());
    }

    public function test_internalMetadataLastModifiedDate_translateInternalToExposed_MetadataLastModifiedDateShouldBeTheSame(
    )
    {
        $actualMetadata = $this->translator->translateInternalToExposed($this->metadata);

        self::assertSame($this->metadata->lastModifiedDate, $actualMetadata->getLastModifiedDate());
    }

    public function test_nullLastModifiedDate_translateInternalToExposed_shouldHaveNullModifiedDate()
    {
        $this->metadata->lastModifiedDate = null;
        $actualMetadata = $this->translator->translateInternalToExposed($this->metadata);

        self::assertNull($actualMetadata->getLastModifiedDate());
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
