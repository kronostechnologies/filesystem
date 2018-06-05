<?php

namespace Kronos\FileSystem;

use Kronos\FileSystem\Exception\FileCantBeWrittenException;
use Kronos\FileSystem\File\File;
use Kronos\FileSystem\File\Metadata;

interface FileSystemInterface
{

    /**
     * @param string $filePath
     * @param string $fileName
     * @return string
     */
    public function put($filePath, $fileName);

    /**
     * @param string $id
     * @return File
     */
    public function get($id);

    /**
     * @param string $id
     * @return string
     */
    public function getUrl($id);

    /**
     * @param string $id
     * @return Metadata
     */
    public function getMetadata($id);

    /**
     * @param string $id
     * @return bool
     */
    public function has($id);

    /**
     * @param string $id
     * @return string
     */
    public function copy($id);

    /**
     * @param string $id
     */
    public function delete($id);

    /**
     * @param string $id
     */
    public function retrieve($id);
}
