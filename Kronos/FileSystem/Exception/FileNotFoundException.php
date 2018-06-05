<?php

namespace Kronos\FileSystem\Exception;


use Exception;

class FileNotFoundException extends Exception
{
    public function __construct($fileUuid)
    {
        parent::__construct("File $fileUuid , can't be found.");
    }

}