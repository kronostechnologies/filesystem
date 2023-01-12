<?php

namespace Kronos\FileSystem\Mount\S3;

use League\Flysystem\Config;

class ConfigToOptionsTranslator
{
    public function translate(Config $config): array
    {
        $options = [];

        $supportedOptions = SupportedOptionsEnum::values();
        foreach($supportedOptions as $supportedOption) {
            $value = $config->get((string)$supportedOption);
            if ($value !== null) {
                $options[$supportedOption] = $value;
            }
        }

        return $options;
    }
}
