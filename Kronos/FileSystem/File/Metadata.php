<?php

namespace Kronos\FileSystem\File;

use DateTime;

class Metadata
{
    protected string $name;
    protected string $mimetype;
    protected ?DateTime $lastModifiedDate;
    protected int $size;

    public function __construct(string $name, string $mimetype, int $size, ?DateTime $lastModifiedDate = null)
    {
        $this->name = $name;
        $this->mimetype = $mimetype;
        $this->size = $size;
        $this->lastModifiedDate = $lastModifiedDate;
    }

    public function getName(): string
    {
        return $this->name;
    }
    public function getMimetype(): string
    {
        return $this->mimetype;
    }

    public function getLastModifiedDate(): ?DateTime
    {
        return $this->lastModifiedDate;
    }

    /**
     * Get the file size in bytes
     */
    public function getSize(): int
    {
        return $this->size;
    }
}
