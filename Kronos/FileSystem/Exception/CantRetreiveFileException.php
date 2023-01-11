<?php

namespace Kronos\FileSystem\Exception;

use Exception;

class CantRetreiveFileException extends Exception
{
    public function __construct($uuid, Exception $previous = null)
    {
        $previousCode = 0;

        if ($previous !== null && is_numeric($previous->getCode())) {
            $previousCode = (int)$previous->getCode();
        }

        parent::__construct("Can't retreive file with uuid : " . $uuid, $previousCode, $previous);
    }
}
