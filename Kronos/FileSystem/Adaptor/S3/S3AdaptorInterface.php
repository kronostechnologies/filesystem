<?php

namespace Kronos\FileSystem\Adaptor\S3;
use Kronos\FileSystem\Adaptor\AdaptorInterface;

/**
 * Interface S3AdaptorInterface
 * @package Kronos\FileSystem\Adaptor\S3
 */
interface S3AdaptorInterface extends AdaptorInterface  {
	/**
	 * Write a new file using a stream.
	 *
	 * @param string $path
	 * @param resource $resource
	 * @param Configuration $config Configuration object
	 *
	 * @return bool
	 */
	public function write($path, $resource, Configuration $config);

	/**
	 * Update a file using a stream.
	 *
	 * @param string $path
	 * @param resource $resource
	 * @param Configuration $config
	 *
	 * @return bool
	 */
	public function update($path, $resource, Configuration $config);
}