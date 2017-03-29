<?php

namespace Kronos\FileSystem\Service\Exception;


use Exception;

class FunctionnalityNotAvailable extends Exception {
	public function __construct($functionnalityName) {
		parent::__construct("$functionnalityName is not available on this service");
	}

}