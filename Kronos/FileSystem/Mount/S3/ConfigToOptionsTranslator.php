<?php

namespace Kronos\FileSystem\Mount\S3;

use League\Flysystem\Config;

class ConfigToOptionsTranslator
{
    public function translate(Config $config): array
    {
        $options = [];

        foreach (SupportedOptionsEnum::cases() as $supportedOption) {
            $value = $config->get($supportedOption->value);

            if ($value !== null) {
                $options[$supportedOption->value] = $value;
            }
        }

        return $options;
    }
}
