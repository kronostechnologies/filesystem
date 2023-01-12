<?php

namespace Kronos\FileSystem\File;

use League\Flysystem\File as FlysystemFile;

class File
{
    private FlysystemFile $file;

    public function __construct(FlysystemFile $file)
    {
        $this->file = $file;
    }

    public function read(): string|false
    {
        return $this->file->read();
    }

    /**
     * Read the file as a stream.
     *
     * @return resource|false
     */
    public function readStream()
    {
        return $this->file->readStream();
    }
}
