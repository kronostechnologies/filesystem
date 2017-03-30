<?php

namespace Kronos\FileSystem\Mount;

interface PathGeneratorInterface {

	/**
	 * @param string $uuid
	 * @return string
	 */
	public function generatePath($uuid);
}