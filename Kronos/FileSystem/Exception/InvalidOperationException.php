<?php

namespace Kronos\FileSystem\Exception;

use Exception;

class InvalidOperationException extends Exception
{
    public function __construct($mountType)
    {
        parent::__construct("Invalid operation for `$mountType` mount type");
    }
}