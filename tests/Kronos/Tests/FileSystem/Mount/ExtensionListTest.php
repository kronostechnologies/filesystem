<?php

namespace Kronos\Tests\FileSystem\Mount;

class ExtensionListTest extends \PHPUnit_Framework_TestCase
{

    const JPG = 'jpg';
    const PNG = 'png';

    const FILENAME = 'filename';

    public function test_FilenameMatchingAddedExtension_isInList_shouldReturnTrue()
    {
        $list = new \Kronos\FileSystem\Mount\ExtensionList();
        $list->addExtension(self::JPG);

        $inList = $list->isInList(self::FILENAME . '.' . self::JPG);

        $this->assertTrue($inList);
    }

    public function test_FilenameNotMatchingAddedExtension_isInList_shouldReturnFalse()
    {
        $list = new \Kronos\FileSystem\Mount\ExtensionList();
        $list->addExtension(self::JPG);

        $inList = $list->isInList(self::FILENAME . '.' . self::PNG);

        $this->assertFalse($inList);
    }

    public function test_ExtensionWithDotAdded_isInList_shouldReturnTrue()
    {
        $list = new \Kronos\FileSystem\Mount\ExtensionList();
        $list->addExtension('.' . self::JPG);

        $inList = $list->isInList(self::FILENAME . '.' . self::JPG);

        $this->assertTrue($inList);
    }

    public function test_FilenameWithoutExtension_isInList_shouldReturnFalse()
    {
        $list = new \Kronos\FileSystem\Mount\ExtensionList();
        $list->addExtension(self::JPG);

        $inList = $list->isInList(self::FILENAME);

        $this->assertFalse($inList);
    }
}