<?php

namespace Kronos\Tests\FileSystem\Mount;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Kronos\FileSystem\Exception\InvalidOperationException;
use Kronos\FileSystem\File\Internal\Metadata;
use Kronos\FileSystem\Mount\GetterInterface;
use Kronos\FileSystem\Mount\ReferenceMount;
use Kronos\FileSystem\PromiseFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReferenceMountTest extends TestCase
{
    const FILENAME = 'filename';
    const UUID = 'uuid';
    const FILEPATH = 'filepath';
    /**
     * @var GetterInterface|MockObject
     */
    private $getter;

    /**
     * @var PromiseFactory|MockObject
     */
    private $promiseFactory;

    /**
     * @var ReferenceMount
     */
    private $mount;

    protected function setUp(): void
    {
        $this->getter = $this->createMock(GetterInterface::class);
        $this->promiseFactory = $this->createMock(PromiseFactory::class);

        $this->mount = new TestableReferenceMount($this->getter, $this->promiseFactory);
    }

    public function test_uuidAndFilename_deleteAsync_shouldReturnRejectedPromise() {
        $expectedPromise = $this->createMock(RejectedPromise::class);
        $this->promiseFactory
            ->expects(self::once())
            ->method('createRejectedPromise')
            ->with(self::isInstanceOf(InvalidOperationException::class))
            ->willReturn($expectedPromise);

        $actualPromise = $this->mount->deleteAsync(self::UUID, self::FILENAME);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_uuidFilePathAndFilename_putAsync_shouldReturnFulfilledPromise() {
        $expectedPromise = $this->createMock(FulfilledPromise::class);
        $this->promiseFactory
            ->expects(self::once())
            ->method('createFulfilledPromise')
            ->with(true)
            ->willReturn($expectedPromise);

        $actualPromise = $this->mount->putAsync(self::UUID, self::FILEPATH, self::FILENAME);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_uuidStreamAndFilename_putStreamAsync_shouldReturnRejectedPromise() {
        $expectedPromise = $this->createMock(RejectedPromise::class);
        $this->promiseFactory
            ->expects(self::once())
            ->method('createRejectedPromise')
            ->with(self::isInstanceOf(InvalidOperationException::class))
            ->willReturn($expectedPromise);

        $actualPromise = $this->mount->putStreamAsync(self::UUID, self::FILEPATH, self::FILENAME);

        self::assertSame($expectedPromise, $actualPromise);
    }

    public function test_uuidAndFilename_hasAsync_shouldReturnFulfilledPromise() {
        $expectedPromise = $this->createMock(FulfilledPromise::class);
        $this->promiseFactory
            ->expects(self::once())
            ->method('createFulfilledPromise')
            ->with(true)
            ->willReturn($expectedPromise);

        $actualPromise = $this->mount->hasAsync(self::UUID, self::FILENAME);

        self::assertSame($expectedPromise, $actualPromise);
    }
}

class TestableReferenceMount extends ReferenceMount
{
    public function getUrl($uuid, $fileName, $forceDownload = false)
    {
        return '';
    }

    public function getMetadata($uuid, $fileName)
    {
        return false;
    }

    public function getMountType()
    {
        return 'TestableRefMount';
    }

}
