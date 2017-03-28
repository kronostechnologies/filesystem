<?php
/**
 * Created by PhpStorm.
 * User: mdemers
 * Date: 2017-03-28
 * Time: 9:34 AM
 */

namespace Kronos\FileSystem\Exception;


use Exception;

class FunctionnalityNotAvailable extends Exception {
	public function __construct($functionnalityName) {
		parent::__construct("$functionnalityName is not available on this service");
	}

}