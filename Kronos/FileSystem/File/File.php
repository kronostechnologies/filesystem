<?php

namespace Kronos\FileSystem\File;


class File
{

    /**
     * @var \League\Flysystem\File
     */
    private $file;

    public function __construct(\League\Flysystem\File $file)
    {
        $this->file = $file;
    }

    /**
     * Read the file.
     *
     * @return string file contents
     */
    public function read()
    {
        return $this->file->read();
    }

    /**
     * Read the file as a stream.
     *
     * @return resource file stream
     */
    public function readStream()
    {
        return $this->file->readStream();
    }
}
