<?php

namespace Kronos\FileSystem;

class ExtensionList
{
    /**
     * @var array
     */
    private $extensions = [];

    public function addExtension($extension)
    {
        $this->extensions[] = strtolower(ltrim($extension, '.'));
    }

    public function isInList($filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if ($extension) {
            return in_array(strtolower($extension), $this->extensions);
        }

        return false;
    }
}
