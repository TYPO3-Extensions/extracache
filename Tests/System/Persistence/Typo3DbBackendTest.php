<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010 AOE media GmbH <dev@aoemedia.de>
 * All rights reserved
 *
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * test case for Tx_Extracache_System_Persistence_Typo3DbBackend
 * @package extracache_tests
 * @subpackage System_Persistence
 */
class Tx_Extracache_System_Persistence_Typo3DbBackendTest extends Tx_Extracache_Tests_AbstractDatabaseTestcase {
	/**
	 * @var array
	 */
	private $pageIds;
	/**
	 * @var Tx_Extracache_System_Persistence_Typo3DbBackend
	 */
	private $typo3DbBackend;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		$this->typo3DbBackend = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extracache_System_Persistence_Typo3DbBackend');
	}
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		unset ( $this->typo3DbBackend );
	}
	
	/**
	 * Test method getPagesWithCacheCleanerStrategyForEvent
	 * @test
	 */
	public function getPagesWithCacheCleanerStrategyForEvent() {
		$this->createTestDB();
		
		$pages = $this->typo3DbBackend->getPagesWithCacheCleanerStrategyForEvent('event_1');
		$this->assertTrue( count($pages) === 2 );
		$this->assertTrue( $pages[0]['uid'] === $this->pageIds[0] );
		$this->assertTrue( $pages[0]['title'] === 'page1' );
		$this->assertTrue( $pages[0]['tx_extracache_cleanerstrategies'] === 'strategy_1' );
		$this->assertTrue( $pages[1]['uid'] === $this->pageIds[1] );
		$this->assertTrue( $pages[1]['title'] === 'page2' );
		$this->assertTrue( $pages[1]['tx_extracache_cleanerstrategies'] === 'strategy_2' );

		$pages = $this->typo3DbBackend->getPagesWithCacheCleanerStrategyForEvent('event_2');
		$this->assertTrue( count($pages) === 1 );
		$this->assertTrue( $pages[0]['uid'] === $this->pageIds[2] );
		$this->assertTrue( $pages[0]['title'] === 'page3' );
		$this->assertTrue( $pages[0]['tx_extracache_cleanerstrategies'] === 'strategy_1,strategy_2' );

		$pages = $this->typo3DbBackend->getPagesWithCacheCleanerStrategyForEvent('event_3');
		$this->assertTrue( count($pages) === 0 );

		$this->dropDatabase();
	}

	/**
	 * creates the test-database and insert records
	 */
	private function createTestDB() {
		$this->createDatabase();
		$db = $this->useTestDatabase();
		$db->admin_query( GeneralUtility::getUrl( PATH_tx_extracache . 'Tests/System/Persistence/Fixtures/SqlQueryForUnittestTypo3DbBackend_createTablePages.txt' ) );

		$this->importExtensions(array('extracache'));
		$this->initializeCommonExtensions();
		$this->importDataSet(PATH_tx_extracache . 'Tests/System/Persistence/Fixtures/TestRecordsForUnittestTypo3DbBackend.xml');

		/********** get UID's of records of certain tables *****/
		$this->pageIds = array();
		$data = $db->exec_SELECTgetRows ( 'uid', 'pages', '');
		foreach ($data as $row) {
			$this->pageIds[] = $row['uid'];
		}
	}
}