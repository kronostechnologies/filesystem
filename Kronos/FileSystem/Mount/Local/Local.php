<?php

namespace Kronos\FileSystem\Mount\Local;

use DateTime;
use Kronos\FileSystem\Exception\CantRetreiveFileException;
use Kronos\FileSystem\File\Internal\Metadata;
use Kronos\FileSystem\Mount\FlySystemBaseMount;
use League\Flysystem\Adapter\Local as LocalFlySystem;
use League\Flysystem\Filesystem;

class Local extends FlySystemBaseMount
{

    const MOUNT_TYPE = 'LOCAL';
    const ERR_MISSING_BASE_URL = 'Mount requires a base URL (setBaseUrl)';

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @param Filesystem $mount
     * @return bool
     */
    protected function isFileSystemValid(Filesystem $mount)
    {
        return $mount->getAdapter() instanceof LocalFlySystem;
    }

    /**
     * Specify the URL to use for getUrl. Note that the file UUID will simply be appended to the URL.
     * @param string $url
     */
    public function setBaseUrl($url)
    {
        $this->baseUrl = $url;
    }

    /**
     * @param string $uuid
     * @param $fileName
     * @return string
     * @throws \Exception
     */
    public function getUrl($uuid, $fileName)
    {
        if (!$this->baseUrl) {
            throw new \Exception(self::ERR_MISSING_BASE_URL);
        }

        return $this->baseUrl . $uuid;
    }

    /**
     * @param string $uuid
     * @param $fileName
     * @throws CantRetreiveFileException
     */
    public function retrieve($uuid, $fileName)
    {
        throw new CantRetreiveFileException($uuid);
    }

    /**
     * @param string $uuid
     * @param $fileName
     * @return bool|Metadata
     */
    public function getMetadata($uuid, $fileName)
    {
        $path = $this->pathGenerator->generatePath($uuid, $fileName);

        if ($localMetadata = $this->mount->getMetadata($path)) {
            $metadata = new Metadata();

            $metadata->size = isset($localMetadata['size']) ? $localMetadata['size'] : 0;
            $metadata->lastModifiedDate = new DateTime('@' . $localMetadata['timestamp']);
            $metadata->mimetype = $this->mount->getMimetype($path);

            return $metadata;
        }

        return false;
    }
}