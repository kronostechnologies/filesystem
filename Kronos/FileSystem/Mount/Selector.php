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
	 * @param string $mountKey
	 * @return MountInterface
	 */
	public function selectMount($mountKey){

	}

	/**
	 * @return MountInterface
	 */
	public function getImportationMount(){

	}
}