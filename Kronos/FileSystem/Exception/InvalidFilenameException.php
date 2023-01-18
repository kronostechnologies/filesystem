<?php

namespace Kronos\FileSystem\Exception;

use Exception;

class InvalidFilenameException extends Exception
{
    public function __construct(string $fileUuid, ?string $filename)
    {
        $filename = $filename === null ? 'null' : 'empty string';

        parent::__construct(
            sprintf(
                'Filename of file with uuid %s must be a non-empty-string, %s given.',
                $fileUuid,
                $filename
            )
        );
    }
}
