<?php
namespace Kronos\FileSystem\File\Internal;

use DateTime;

class Metadata {

	/**
	 * @var string
	 */
	public $name;

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