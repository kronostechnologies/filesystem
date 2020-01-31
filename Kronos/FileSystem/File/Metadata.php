<?php

namespace Kronos\FileSystem\File;


use DateTime;

class Metadata
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $mimetype;

    /**
     * @var DateTime
     */
    protected $lastModifiedDate;

    /**
     * @var int (bytes)
     */
    protected $size;

    /**
     * Metadata constructor.
     * @param string $name
     * @param string $mimetype
     * @param DateTime $lastModifiedDate
     * @param int $size
     */
    public function __construct(string $name, string $mimetype, int $size, DateTime $lastModifiedDate = null)
    {
        $this->name = $name;
        $this->mimetype = $mimetype;
        $this->size = $size;
        $this->lastModifiedDate = $lastModifiedDate;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getMimetype(): string
    {
        return $this->mimetype;
    }

    /**
     * @return DateTime
     */
    public function getLastModifiedDate(): ?DateTime
    {
        return $this->lastModifiedDate;
    }

    /**
     * @return int (bytes)
     */
    public function getSize(): int
    {
        return $this->size;
    }
}
