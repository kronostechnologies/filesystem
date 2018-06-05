<?php

namespace Kronos\FileSystem\Exception;


use Exception;

class WrongFileSystemTypeException extends Exception
{
    public function __construct($expectedType, $actualTypeClass)
    {
        parent::__construct("Expected $expectedType but got $actualTypeClass");
    }

}