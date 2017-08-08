<?php

namespace Kronos\FileSystem\Mount;

interface PathGeneratorInterface {

	/**
	 * @param $uuid
	 * @param $fileName
	 * @return mixed
	 */
	public function generatePath($uuid, $fileName);
}