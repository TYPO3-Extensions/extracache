<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 AOE media GmbH <dev@aoemedia.de>
*  All rights reserved
*
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * 
 */
class Tx_Extracache_Persistence_Typo3DbBackend {
	/**
	 * @param string $sqlFrom
	 * @param string $sqlWhere
	 */
	public function deleteQuery($sqlFrom, $sqlWhere) {
		$this->getTypo3Db()->exec_DELETEquery ( $sqlFrom, $sqlWhere );
	}
	/**
	 * @param string $sqlFrom
	 * @param string $sqlWhere
	 * @param array $modifiedValues
	 */
	public function updateQuery($sqlFrom, $sqlWhere, $modifiedValues) {
		$this->getTypo3Db()->exec_UPDATEquery ( $sqlFrom, $sqlWhere, $modifiedValues );
	}
	/**
	 * @param string $sqlSelect
	 * @param string $sqlFrom
	 * @param string $sqlWhere
	 * @param string $sqlOrderBy
	 * @param string $sqlLimit
	 * @return array
	 */
	public function selectQuery($sqlSelect, $sqlFrom, $sqlWhere, $sqlOrderBy='', $sqlLimit='') {
		return $this->getTypo3Db()->exec_SELECTgetRows ( $sqlSelect, $sqlFrom, $sqlWhere, '', $sqlOrderBy, $sqlLimit );
	}

	/**
	 * @return	t3lib_DB
	 * @throws	RuntimeException
	 */
	protected function getTypo3Db() {
		global $TYPO3_DB;
		
		// create link if link doesn't exist
		if($TYPO3_DB->link === FALSE) {
			if (!(
					TYPO3_db_host && TYPO3_db_username && TYPO3_db_password && TYPO3_db &&
					$TYPO3_DB->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password) &&
					$TYPO3_DB->sql_select_db(TYPO3_db)
			)) {
				throw new RuntimeException('Could not connect to TYPO3 database.');
			}
		}
		
		return $TYPO3_DB;
	}
}