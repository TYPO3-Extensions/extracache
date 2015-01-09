<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

define ( 'PATH_tx_extracache', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath ( 'extracache' ) );

if (!defined('PATH_tslib')) {
	define('PATH_tslib', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('cms') . 'tslib/');
}

// start bootstrap
require_once(PATH_tx_extracache . 'Classes/Bootstrap.php');
Bootstrap::start();