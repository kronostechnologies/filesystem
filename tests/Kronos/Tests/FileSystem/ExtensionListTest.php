<?php

namespace Kronos\Tests\FileSystem;

use Kronos\FileSystem\ExtensionList;
use PHPUnit\Framework\TestCase;

class ExtensionListTest extends TestCase
{

    const JPG = 'jpg';
    const PNG = 'png';
    const PNG_UPPERCASE = 'PNG';

    const FILENAME = 'filename';

    public function test_FilenameMatchingAddedExtension_isInList_shouldReturnTrue()
    {
        $list = new ExtensionList();
        $list->addExtension(self::JPG);

        $inList = $list->isInList(self::FILENAME . '.' . self::JPG);

        $this->assertTrue($inList);
    }

    public function test_FilenameNotMatchingAddedExtension_isInList_shouldReturnFalse()
    {
        $list = new ExtensionList();
        $list->addExtension(self::JPG);

        $inList = $list->isInList(self::FILENAME . '.' . self::PNG);

        $this->assertFalse($inList);
    }

    public function test_ExtensionWithDotAdded_isInList_shouldReturnTrue()
    {
        $list = new ExtensionList();
        $list->addExtension('.' . self::JPG);

        $inList = $list->isInList(self::FILENAME . '.' . self::JPG);

        $this->assertTrue($inList);
    }

    public function test_FilenameWithoutExtension_isInList_shouldReturnFalse()
    {
        $list = new ExtensionList();
        $list->addExtension(self::JPG);

        $inList = $list->isInList(self::FILENAME);

        $this->assertFalse($inList);
    }

    public function test_FilenameWithExtensionUppercase_isInListButLowercase_shouldReturnTrue()
    {
        $list = new ExtensionList();
        $list->addExtension(self::PNG);

        $inList = $list->isInList(self::FILENAME . '.' . self::PNG_UPPERCASE);

        $this->assertTrue($inList);
    }

    public function test_FilenameWithExtensionLowercase_isInListButUppercase_shouldReturnTrue()
    {
        $list = new ExtensionList();
        $list->addExtension(self::PNG_UPPERCASE);

        $inList = $list->isInList(self::FILENAME . '.' . self::PNG);

        $this->assertTrue($inList);
    }
}
