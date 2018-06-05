<?php

namespace Kronos\FileSystem;

use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Metadata;

interface FileRepositoryInterface
{

    /**
     * @param string $mountType
     * @param string $fileName
     * @return string $uuid
     */
    public function addNewFile($mountType, $fileName);

    /**
     * @param string $uuid
     * @return string
     */
    public function getFileMountType($uuid);

    /**
     * @param string $uuid
     * @return string
     */
    public function getFileName($uuid);

    /**
     * @param string $uuid
     * @return bool
     */
    public function delete($uuid);

    /**
     * @param string $uuid
     * @param string $mountType
     * @return bool
     */
    public function update($uuid, $mountType);
}