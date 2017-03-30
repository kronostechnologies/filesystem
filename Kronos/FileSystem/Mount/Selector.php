<?php

namespace Kronos\FileSystem\Mount;


class Selector {

	/**
	 * @param MountInterface $mount
	 */
	public function addMount(MountInterface $mount){

	}

	/**
	 * @param string $mountKey
	 */
	public function setImportationMount($mountKey){

	}

	/**
	 * Maybe an object representing the file location (uuid + mountType)
	 *
	 * @param string $mountKey
	 */
	public function selectMount($mountKey){

	}

	/**
	 * @return MountInterface
	 */
	public function getImportationMount(){

	}
}