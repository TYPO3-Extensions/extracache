<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2009 AOE media GmbH <dev@aoemedia.de>
 * All rights reserved
 *
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * File cache entry
 * @package extracache
 */
class Tx_Extracache_Domain_Model_CacheFile {
	/**
	 * @var integer
	 */
	private $lastModificationTime;
	/**
	 * @var string
	 */
	private $name;

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return base64_encode ( $this->name );
	}
	/**
	 * @return integer
	 */
	public function getLastModificationTime() {
		return $this->lastModificationTime;
	}
	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param integer $lastModificationTime
	 */
	public function setLastModificationTime($lastModificationTime) {
		$this->lastModificationTime = $lastModificationTime;
	}
	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}
}