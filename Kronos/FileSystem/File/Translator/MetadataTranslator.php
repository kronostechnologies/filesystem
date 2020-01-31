<?php

namespace Kronos\FileSystem\File\Translator;

use Kronos\FileSystem\File\Metadata;

class MetadataTranslator
{

    /**
     * @param \Kronos\FileSystem\File\Internal\Metadata $internalMetadata
     * @return Metadata
     */
    public function translateInternalToExposed(\Kronos\FileSystem\File\Internal\Metadata $internalMetadata)
    {
        return new Metadata($internalMetadata->name,
            $internalMetadata->mimetype,
            $internalMetadata->size,
            $internalMetadata->lastModifiedDate
        );
    }
}
