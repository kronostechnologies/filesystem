<?php

namespace Kronos\FileSystem\Mount;


use Kronos\FileSystem\Exception\MountNotFoundException;

class Selector {

	/**
	 * @var array
	 */
	protected $mountList = [];

	/**
	 * @var string
	 */
	protected $importationMountKey = '';

	/**
	 * @param MountInterface $mount
	 */
	public function addMount(MountInterface $mount){
		$this->mountList[$mount->getMountType()] = $mount;
	}

	/**
	 * @param string $mountKey
	 * @throws MountNotFoundException
	 */
	public function setImportationMount($mountKey){
		if(isset($this->mountList[$mountKey])){
			$this->importationMountKey = $mountKey;
		}
		else{
			throw new MountNotFoundException($mountKey);
		}
	}

	/**
	 * @param string $mountKey
	 * @return MountInterface
	 * @throws MountNotFoundException
	 */
	public function selectMount($mountKey){
		if(isset($this->mountList[$mountKey])){
			return $this->mountList[$mountKey];
		}

		throw new MountNotFoundException($mountKey);
	}

	/**
	 * @return MountInterface|null
	 */
	public function getImportationMount(){

		if(!empty($this->importationMountKey)){
			return $this->mountList[$this->importationMountKey];
		}

		return null;
	}

	/**
	 * @return string
	 */
	public function getImportationMountType() {
		return $this->importationMountKey;
	}
}