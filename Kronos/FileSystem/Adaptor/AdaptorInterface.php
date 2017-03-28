<?php

namespace Kronos\FileSystem\Adaptor;

/**
 *
 * Interface AdaptorInterface
 * @package Kronos\FileSystem\Adaptor
 */
interface AdaptorInterface {

	/**
	 * @param string $path
	 * @return resource
	 */
	public function getRessource($path);

	/**
	 * @param string $path
	 * @return string
	 */
	public function getSignedUrl($path);

	/**
	 * Delete a file.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function delete($path);
}