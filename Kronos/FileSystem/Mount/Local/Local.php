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
     * @var string
     */
    protected $forceDownloadParameter = '';

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

    public function setForceDownloadParameter($parameter)
    {
        $this->forceDownloadParameter = $parameter;
    }

    /**
     * @param $uuid
     * @param $fileName
     * @param bool $forceDownload
     * @return mixed|string
     * @throws \Exception
     */
    public function getUrl($uuid, $fileName, $forceDownload = false)
    {
        if (!$this->baseUrl) {
            throw new \Exception(self::ERR_MISSING_BASE_URL);
        }

        return $this->baseUrl . $uuid . ($forceDownload ? $this->forceDownloadParameter : '');
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
     * @return Metadata|false
     */
    public function getMetadata($uuid, $fileName)
    {
        $path = $this->pathGenerator->generatePath($uuid, $fileName);

        if ($localMetadata = $this->mount->getMetadata($path)) {
            $metadata = new Metadata();

            $metadata->size = isset($localMetadata['size']) ? $localMetadata['size'] : 0;
            $metadata->lastModifiedDate = new DateTime('@' . $localMetadata['timestamp']);

            if ($mimeType = $this->mount->getMimetype($path)) {
                $metadata->mimetype = $mimeType;
            }

            return $metadata;
        }

        return false;
    }
}
