<?php

namespace Kronos\FileSystem\Exception;

use Exception;
use Throwable;

class FileCantBeWrittenException extends Exception
{
    public function __construct($mountType, Throwable $parent = null)
    {
        parent::__construct("File couldn't have been wrote for `$mountType` mount type", 0, $parent);
    }


}
