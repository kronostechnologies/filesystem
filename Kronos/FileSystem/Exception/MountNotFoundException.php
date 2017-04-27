<?php

namespace Kronos\FileSystem\Exception;


use Exception;

class MountNotFoundException extends Exception{
	public function __construct($mountKey) {
		parent::__construct('mount not found : '.$mountKey);
	}

}