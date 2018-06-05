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
    public function __construct($name, $mimetype, DateTime $lastModifiedDate, $size)
    {
        $this->name = $name;
        $this->mimetype = $mimetype;
        $this->lastModifiedDate = $lastModifiedDate;
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getMimetype()
    {
        return $this->mimetype;
    }

    /**
     * @return DateTime
     */
    public function getLastModifiedDate()
    {
        return $this->lastModifiedDate;
    }

    /**
     * @return int (bytes)
     */
    public function getSize()
    {
        return $this->size;
    }


}