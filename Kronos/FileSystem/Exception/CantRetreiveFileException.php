<?php

namespace Kronos\FileSystem\Exception;


use Exception;

class CantRetreiveFileException extends Exception
{
    public function __construct($uuid, Exception $previous = null)
    {
        parent::__construct("Can't retreive file with uuid : " . $uuid, $previous->getCode(), $previous);
    }

}