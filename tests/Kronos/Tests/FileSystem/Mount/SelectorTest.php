<?php
namespace Kronos\Tests\FileSystem\Mount;

use Kronos\FileSystem\Exception\MountNotFoundException;
use Kronos\FileSystem\Mount\MountInterface;
use Kronos\FileSystem\Mount\Selector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SelectorTest extends TestCase{

	const MOUNT_KEY = 'MOUNT_KEY';
	/**
	 * @var MountInterface|MockObject
	 */
	private $mount;

	/**
	 * @var Selector
	 */
	private $selector;

	public function setUp(): void {
		$this->mount = $this->createMock(MountInterface::class);

		$this->selector = new Selector();
	}

	public function test_mountDoesntExistInSelector_selectMount_ShouldthrowException(){
		self::expectException(MountNotFoundException::class);

		$this->selector->selectMount(self::MOUNT_KEY);
	}

	public function test_mountIsAdded_selectMount_ShouldReturnMount(){
		$this->mount->method('getMountType')->willReturn(self::MOUNT_KEY);

		$this->selector->addMount($this->mount);

		$selectedMount = $this->selector->selectMount(self::MOUNT_KEY);

		self::assertSame($this->mount,$selectedMount);
	}

	public function test_mountDoesntExistInSelector_setImportationMount_ShouldthrowException(){
		self::expectException(MountNotFoundException::class);

		$this->selector->setImportationMount(self::MOUNT_KEY);
	}

	public function test_noImportationMountIsSelected_getImportationMount_ShouldReturnNull(){
		$importationMount = $this->selector->getImportationMount();

		self::assertNull($importationMount);
	}

	public function test_importationMountIsSet_getImportationMount_ShouldReturnMount(){
		$this->mount->method('getMountType')->willReturn(self::MOUNT_KEY);

		$this->selector->addMount($this->mount);
		$this->selector->setImportationMount(self::MOUNT_KEY);

		$importationMount = $this->selector->getImportationMount();

		self::assertSame($this->mount,$importationMount);
	}

	public function test_AddMountWichMountTypeIsAlreadyAdded_addMount_ShouldOverwriteMount(){
		$this->mount->method('getMountType')->willReturn(self::MOUNT_KEY);
		$this->selector->addMount($this->mount);

		$mount2 = $this->createMock(MountInterface::class);
		$mount2->method('getMountType')->willReturn(self::MOUNT_KEY);

		$this->selector->addMount($mount2);

		$selectedMount = $this->selector->selectMount(self::MOUNT_KEY);

		self::assertSame($mount2,$selectedMount);
	}
}
