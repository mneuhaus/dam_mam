<?php

class tx_dammam_usage_exporter extends tx_scheduler_Task {

	public function execute() {
		try {
			$target = realpath($this->exportfolder);
			$references = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_dam_mm_ref', '1=1');
			$data = new SimpleXMLElement('<?xml version="1.0"?><usages></usages>');
			foreach ($references as $reference) {
				$assets = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_dam', 'uid=' . $reference['uid_local']);
				if (count($assets) > 0) {
					$asset = current($assets);
					$usages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $reference['tablenames'], 'uid=' . $reference['uid_foreign']);
					if (count($usages) > 0) {
						$usage = current($usages);
						$pages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'pages', 'uid=' . $usages[0]['pid']);
						if (count($pages) > 0) {
							$page = current($pages);
							$this->log('Ref', array(
								'page' => $page,
								'asset' => $asset,
								'usage' => $usage,
								'reference' => $reference
							));
							$child = $data->addChild('usage', $child);
							$child->addChild('page_title', $page['title']);
							$child->addChild('mam_uuid', $asset['tx_dammam_mamuuid']);
							$child->addChild('page_link', $this->getLink($page['uid']));
						}
					}
				}
			}
			$this->log('XML Output', array(
				$GLOBALS['TSFE']->config['config']['baseURL'],
				$data->asXML()
			));
			file_put_contents($target . '/usage.xml', $data->asXML());
		} catch (Exception $e) {
			$this->log('There was an unexpected Error: ' . $e->getMessage(), array( 'Exception' => $e ), 3);
		}

		return TRUE;
	}

	public function log($msg, $data = NULL, $severity = 0) {
		if ($severity >= $this->debuglevel) {
			$data = $this->objectToArray($data);
			t3lib_div::devLog($msg, 'dam_mam', $severity, $data);

			$severities = array(
				0 => E_USER_NOTICE,
				1 => E_USER_NOTICE,
				2 => E_WARNING,
				3 => E_WARNING,
				-1 => E_USER_NOTICE
			);

			unset($data);
		}
	}

	public function objectToArray($object) {
		if (is_object($object)) {
			$array = get_object_vars($object);
		} elseif (is_array($object)) {
			$array = $object;
		}

		if (is_array($array)) {
			foreach ($array as $key => $value) {
				if (is_object($value)) {
					$array[$key] = $this->objectToArray($value);
				}
			}
		}
		return $array;
	}

	public function getLink($pageUid) {
		$this->initTSFE($pageUid);
    	$cObj = t3lib_div::makeInstance('tslib_cObj');
    	return rtrim($GLOBALS['TSFE']->config['config']['baseURL'], '/') . '/' . ltrim($cObj->getTypoLink_URL($pageUid), '/');
	}

	public function initTSFE($pageUid=1) {
		require_once(PATH_tslib.'class.tslib_fe.php');
		require_once(PATH_t3lib.'class.t3lib_userauth.php');
		require_once(PATH_tslib.'class.tslib_feuserauth.php');
		require_once(PATH_t3lib.'class.t3lib_cs.php');
		require_once(PATH_tslib.'class.tslib_content.php');
		require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
		require_once(PATH_t3lib.'class.t3lib_page.php');

		$TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');

		if (!is_object($GLOBALS['TT'])) {
			$GLOBALS['TT'] = new t3lib_timeTrack;
			$GLOBALS['TT']->start();
		}

		$GLOBALS['TSFE'] = new $TSFEclassName($GLOBALS['TYPO3_CONF_VARS'], $pageUid, '0', 1, '', '', '', '');
		$GLOBALS['TSFE']->connectToMySQL();
		$GLOBALS['TSFE']->initFEuser();
		$GLOBALS['TSFE']->fetch_the_id();
		$GLOBALS['TSFE']->getPageAndRootline();
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->tmpl->getFileName_backPath = PATH_site;
		$GLOBALS['TSFE']->forceTemplateParsing = 1;
		$GLOBALS['TSFE']->getConfigArray();
	}
}

?>