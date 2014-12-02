<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 AOE GmbH <dev@aoe.com>
*  All rights reserved
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Defines an overlay for TSFE to get a better performance in static cache disposal.
 * This "light TSFE" is used together with the Tx_Extracache_System_Tools_ObjectProxy
 * in case of static caching context and offers the possibilit to still use internals
 * like templates, content rendering or link generation if required.
 *
 * @package extracache
 * @subpackage Typo3
 */
class Tx_Extracache_Typo3_Frontend extends tslib_fe {
	/**
	 * Member of original TSFE object.
	 * @var boolean Indicates that cached content is delivered
	 */
	public $cacheContentFlag = 1;
	/**
	 * Configuration array with TypoScript [config.]
	 * @var array
	 */
	public $config = array();
	/**
	 * Defines an object proxy that will load the real object when it's required.
	 * @var Tx_Extracache_System_Tools_ObjectProxy
	 */
	public $csConvObj;
	/**
	 * Defines an object proxy that will load the real object when it's required.
	 * @var Tx_Extracache_System_Tools_ObjectProxy
	 */
	public $sys_page;
	/**
	 * Defines an object proxy that will load the real object when it's required.
	 * @var Tx_Extracache_System_Tools_ObjectProxy
	 */
	public $tmpl;

	/**
	 * @var integer
	 */
	protected $firstRootlineId;

	/**
	 * Constructs this object as a light-weight TSFE.
	 *
	 * @param mixed $id UID (integer) or alias (string) of the current page
	 * @param integer $type typeNum of the current page
	 * @param string $mountPoint The mount point of the current page
	 * @return void
	 */
	public function __construct($id, $type = 0, $mountPoint = '') {
		$this->TYPO3_CONF_VARS = $GLOBALS['TYPO3_CONF_VARS'];

		$this->id = $id;
		$this->type = $type;

		$this->clientInfo = $GLOBALS['CLIENT'];
		$this->uniqueString = md5(microtime());

		$this->initializeConfiguration();
		$this->initializeObjects();

			// Initializes the TYPO3 caching framework for core caches:
		if (TYPO3_UseCachingFramework) {
			$this->initCaches();
		}
			// Use mount point information if enabled:
		if ($this->TYPO3_CONF_VARS['FE']['enable_mount_pids']) {
			$this->MP = (string)$mountPoint;
		}

			// Define charsets since no configuration could be available at this place
		$this->defaultCharSet = $this->renderCharset = $this->metaCharset = 'utf-8';
	}

	/**
	 * Finalizes the initialization of the frontend user object.
	 *
     * @param Tx_Extracache_Xclass_FrontendUserAuthentication $feUser
     * @return void
	 */
	public function finalizeFrontendUser(Tx_Extracache_Xclass_FrontendUserAuthentication $feUser) {
        $this->fe_user = $feUser;

            // Unpacks the user contents if not set:
        if (!isset($this->fe_user->uc)) {
            $this->fe_user->unpack_uc('');
        }

            // Fetches the session data if not set:
        if (!isset($this->fe_user->sesData) || !count($this->fe_user->sesData)) {
            $this->fe_user->fetchSessionData();
        }

            // Sets the group list of the logged in(!) frontend user:
        if ($this->fe_user->isValidFrontendUser()) {
            $this->loginUser = 1;
        }
        $this->gr_list = $this->fe_user->getGroupList();
	}

	/**
	 * Initializes the page select object.
	 * This method gets called as callback when the real object is created in the proxy object.
	 *
	 * @param t3lib_pageSelect $pageSelect The page select object
	 * @return void
	 */
	public function initializePageSelectCallback(t3lib_pageSelect $pageSelect) {
		$pageSelect->init(false);
		$this->setSysPageWhereClause();
		$this->rootLine = $this->sys_page->getRootLine($this->id, $this->MP);
		$this->page = $this->sys_page->getPage($this->id);
			// Load TCA stuff since enableFields() relies on TCA:
		$this->getCompressedTCarray();
	}
	/**
	 * Initializes the template object.
	 * This method gets called as callback when the real object is created in the proxy object.
	 *
	 * @param t3lib_TStemplate $template The template object
	 * @return void
	 */
	public function initializeTemplateCallback(t3lib_TStemplate $template) {
		$template->init();
		// typolink checks against linksaccrossdomains and thus needs at least the first rootline id:
		if ($this->firstRootlineId) {
			$template->rootLine[0]['uid'] = $this->firstRootlineId;
		}
	}

	/**
	 * Merges additional configuration with the current configuration and set config 'absRefPrefix'
	 *
	 * @param array $configuration
	 * @return void
	 */
	public function mergeConfiguration(array $configuration) {
		// Merges additional configuration with the current configuration
		$this->config = t3lib_div::array_merge_recursive_overrule($this->config, $configuration);

		// Set config 'absRefPrefix' (this is needed to build correct links, if this config is set)
		if(array_key_exists('config', $this->config) && is_array($this->config['config']) && array_key_exists('absRefPrefix', $this->config['config'])) {
			$this->absRefPrefix = $this->config['config']['absRefPrefix'];
		}
	}

	/**
	 * @param integer $firstRootlineId
	 * @return void
	 */
	public function setFirstRootlineId($firstRootlineId) {
		$this->firstRootlineId = $firstRootlineId;
	}

	/**
	 * @return Tx_Extracache_Domain_Repository_ArgumentRepository
	 */
	protected function getArgumentRepository() {
		/** @var $configurationManager Tx_Extracache_Configuration_ConfigurationManager */
		$configurationManager = t3lib_div::makeInstance('Tx_Extracache_Configuration_ConfigurationManager');
		return $configurationManager->getArgumentRepository();
	}

	/**
	 * Initializes a basic configuration of the TSFE object.
	 *
	 * @return void
	 */
	protected function initializeConfiguration() {
		$this->config = array(
			'config' => array(),
			'mainScript' => 'index.php',
		);

		$frontendConfigArguments = $this->getArgumentRepository()->getArgumentsByType(Tx_Extracache_Domain_Model_Argument::TYPE_frontendConfig);
		/** @var $frontendConfigArgument Tx_Extracache_Domain_Model_Argument */
		foreach ($frontendConfigArguments as $frontendConfigArgument) {
			$this->config[$frontendConfigArgument->getName()] = $frontendConfigArgument->getValue();
		}
	}
	/**
	 * Initializes the class objects to use a proxy in the first step.
	 *
	 * @return void
	 */
	protected function initializeObjects() {
        $this->cObj = t3lib_div::makeInstance('Tx_Extracache_System_Tools_ObjectProxy', $this, 'tslib_cObj');
		$this->csConvObj = t3lib_div::makeInstance('Tx_Extracache_System_Tools_ObjectProxy', $this, 't3lib_cs');
		$this->sys_page = t3lib_div::makeInstance(
			'Tx_Extracache_System_Tools_ObjectProxy',
			$this, 't3lib_pageSelect', 'initializePageSelectCallback'
		);
		$this->tmpl = t3lib_div::makeInstance(
			'Tx_Extracache_System_Tools_ObjectProxy',
			$this, 't3lib_TStemplate', 'initializeTemplateCallback'
		);
	}
}