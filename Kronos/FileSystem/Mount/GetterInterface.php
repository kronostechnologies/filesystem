<?php

namespace Kronos\FileSystem\Mount;

use Kronos\FileSystem\File\File;

interface GetterInterface
{
    /**
     * @param string $uuid
     * @param $fileName
     * @return File
     */
    public function get($uuid, $fileName);
}