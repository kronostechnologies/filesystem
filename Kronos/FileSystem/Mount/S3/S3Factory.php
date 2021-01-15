<?php

namespace Kronos\FileSystem\Mount\S3;

use League\Flysystem\Filesystem;

class S3Factory
{
    public function createAsyncUploader(Filesystem $mount): AsyncAdapter
    {
        return new AsyncAdapter($mount);
    }
}
