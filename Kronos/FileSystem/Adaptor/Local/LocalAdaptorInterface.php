<?php

namespace Kronos\FileSystem\Adaptor\Local;
use Kronos\FileSystem\Adaptor\AdaptorInterface;

/**
 * Interface LocalAdaptorInterface
 * @package Kronos\FileSystem\Adaptor\Local
 */
interface LocalAdaptorInterface extends AdaptorInterface  {
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