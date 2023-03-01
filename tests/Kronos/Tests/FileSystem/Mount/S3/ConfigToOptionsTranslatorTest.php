<?php

namespace Kronos\Tests\FileSystem\Mount\S3;

use Kronos\FileSystem\Mount\S3\ConfigToOptionsTranslator;
use Kronos\FileSystem\Mount\S3\SupportedOptionsEnum;
use Kronos\Tests\FileSystem\ExtendedTestCase;
use League\Flysystem\Config;
use PHPUnit\Framework\MockObject\MockObject;

class ConfigToOptionsTranslatorTest extends ExtendedTestCase
{
    const STORAGE_CLASS_VALUE = 'storage class value';

    private Config&MockObject $config;
    private ConfigToOptionsTranslator $translator;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);

        $this->translator = new ConfigToOptionsTranslator();
    }

    public function test_config_translate_shouldGetAllSupportedOptions(): void
    {
        $supportedOptions = SupportedOptionsEnum::cases();
        $this->config
            ->expects(self::exactly(count($supportedOptions)))
            ->method('get')
            ->with(
                ...self::withConsecutive(
                    ...array_map(
                        function ($option) {
                            return [$option->value];
                        },
                        $supportedOptions
                    )
                )
            );

        $this->translator->translate($this->config);
    }

    public function test_optionInConfig_translate_shouldReturnOptionWithConfigValue(): void
    {
        $this->config
            ->method('get')
            ->willReturnCallback(
                function ($optionName) {
                    return $optionName === SupportedOptionsEnum::STORAGE_CLASS->value ? self::STORAGE_CLASS_VALUE : null;
                }
            );

        $options = $this->translator->translate($this->config);

        self::assertCount(1, $options);
        self::assertArrayHasKey(SupportedOptionsEnum::STORAGE_CLASS->value, $options);
        self::assertEquals(self::STORAGE_CLASS_VALUE, $options[SupportedOptionsEnum::STORAGE_CLASS->value]);
    }
}
