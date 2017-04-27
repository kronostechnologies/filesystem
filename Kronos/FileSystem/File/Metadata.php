<?php

namespace Kronos\FileSystem\File;


use DateTime;

class Metadata {

	/**
	 * @var string
	 */
	public $mimetype;

	/**
	 * @var DateTime
	 */
	public $lastModifiedDate;

	/**
	 * @var int (bytes)
	 */
	public $size;
}