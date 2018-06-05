<?php

namespace Kronos\FileSystem\Exception;

use Exception;

class FileCantBeWrittenException extends Exception
{
    public function __construct($mountType)
    {
        parent::__construct("File couldn't have been wrote for `$mountType` mount type");
    }


}