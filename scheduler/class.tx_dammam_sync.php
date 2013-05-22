<?php

/**
 *
 */
class tx_dammam_sync extends tx_scheduler_Task {
	protected $moveProcessedFiles = TRUE;

	/**
	 * @var array
	 */
	protected $processed = array();

	/**
	 * @var boolean
	 */
	protected $import_missing = FALSE;

	/**
	 *
	 * @var array
	 */
	protected $missing_files = array();

	/**
	 *
	 * @var array
	 */
	protected $found_files = array();

	/**
	 * @var array
	 */
	protected $ignoredMimeTypes = array(
		'application/octet-stream'
	);

	/**
	 * @var array
	 */
	protected $stat = array(
		'start' => 0,
		'stop' => 0,
		'files_inserted' => 0,
		'files_updated' => 0,
		'files_deleted' => 0,
		'files_missing' => 0
	);

	public function execute() {
		ob_start();
		try {
			$this->stat['start'] = function_exists('microtime') ? microtime(TRUE) : time();

			if ($this->loadConf() == FALSE) {
				return FALSE;
			}
			$GLOBALS['DebugLevel'] = $this->debugLevel;
			$GLOBALS['TYPO3_DB']->debugOutput = 0;

			$this->sanitize();

			try {
				$this->process();
			} catch (Exception $e) {
				$this->log('The import failed!', $e, 3);
				return FALSE;
			}

			//$this->redoRelations();
			$this->cleanUpAbandonedFiles($this->mediafolder);
			$this->cleanUpAbandonedFolders($this->mediafolder);

			$this->stat['stop'] = function_exists('microtime') ? microtime(TRUE) : time();

			$s = $this->stat;
			$this->log('Import statistics', array(
				'Total time' => ($s['stop'] - $s['start']),
				'New Files' => $s['files_inserted'],
				'Updated Files' => $s['files_updated'],
				'Deleted Files' => $s['files_deleted'],
				'Missing Files' => $s['files_missing'],
				'Total Files processd' => ($s['files_inserted'] + $s['files_updated'] + $s['files_deleted'] + $s['files_missing']),
				'File per second' => ($s['files_inserted'] + $s['files_updated'] + $s['files_deleted'] + $s['files_missing']) / ($s['stop'] - $s['start']),
				'Processed Files' => $this->processed
			), 1);
		} catch (Exception $e) {
			$this->log('There was an unexpected Error: ' . $e->getMessage(),
				array(
					'Exception' => $this->objectToArray($e)
				), 3);
		}

		$output = ob_get_clean();
		if (!empty($output)) {
			$this->log('Unexpected output from Scheduler', array($output), 3);
		}


		return TRUE;
	}

	public function log($msg, $data = NULL, $severity = 0, $notify = FALSE) {
		if ($severity >= $this->debuglevel || $notify === TRUE) {
			$data = $this->objectToArray($data);
		}

		if ($severity >= $this->debuglevel) {
			t3lib_div::devLog($msg, 'dam_mam', $severity, $data);
		}

		if ($notify === TRUE) {
			$mail = t3lib_div::makeInstance('t3lib_mail_Message');
			t3lib_div::devLog($this->notificationEmails, 'dam_mam', $severity, $data);
			$mail->setTo(explode(',', $this->notificationEmails));
			$mail->setSubject($msg);
			$mail->setBody(var_export($data, TRUE));
			$mail->send();
		}
		unset($data);
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

	public function sanitize() {
		$this->hotfolder = realpath($this->hotfolder) . '/';
		$this->mediafolder = realpath($this->mediafolder) . '/';
		$this->trashfolder = realpath($this->mediafolder) . '/.trash/';
		$this->max = intval($this->max);
		$this->media_pid = intval($this->media_pid);
	}

	public function process() {
		$files = scandir($this->hotfolder);
		$counter = 0;
		foreach ($files as $file) {
			if (substr($file, 0, 1) == '.') {
				continue;
			}

			if (stristr($file, 'missing_')) {
				$this->rename($file, $this->hotfolder . '/missing/' . basename($file));
			}
			if (!stristr($file, 'export')) {
				if (!is_dir($file) && basename($file) !== 'conf.json') {
					// $this->log("Skipping: " . basename($file), array(), 0);
				}
				continue;
			}

			if ($counter >= $this->max) {
				return;
			}
			$counter++;

			$file = $this->hotfolder . $file;

			if ($file) {
				$this->log('Loading: ' . basename($file), array(), 0);

				$content = file_get_contents($file);
				if (empty($content)) {
					$this->log('The file ' . basename($file) . ' is empty.',
						array(), 1);
					$this->rename($file, dirname($file) . '/failed/' . basename($file));
					continue;
				}

				$result = json_decode($content);
					// convert complex xe+ notated numbers into strings, because json_decode can't handle them
				if (!is_a($result, 'stdClass')) {
					$content = preg_replace('/"number": *(.+e\+.+?)(,*)/', '"number": "$1"$2', $content);
					$result = json_decode($content);
				}

				if (!is_a($result, 'stdClass')) {
					switch (json_last_error()) {
						case JSON_ERROR_NONE:
							$jsonError = ' - No errors';
							break;
						case JSON_ERROR_DEPTH:
							$jsonError = ' - Maximum stack depth exceeded';
							break;
						case JSON_ERROR_STATE_MISMATCH:
							$jsonError = ' - Underflow or the modes mismatch';
							break;
						case JSON_ERROR_CTRL_CHAR:
							$jsonError = ' - Unexpected control character found';
							break;
						case JSON_ERROR_SYNTAX:
							$jsonError = ' - Syntax error, malformed JSON';
							break;
						case JSON_ERROR_UTF8:
							$jsonError = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
							break;
						default:
							$jsonError = ' - Unknown error';
							break;
					}
					$this->log('The file ' . basename($file) . ' could not be loaded',
						array(
							'Error:' => 'The file "' . $file . '" can not be read or has syntax errors.',
							'Hint:' => 'Please check that the apache user has rights to the read the file and that it is a valid json document',
							'JSON Error' => $jsonError
						), 3);
					$this->rename($file, dirname($file) . '/failed/' . basename($file));
					continue;
				}

				$this->takeAction($result->data);
				unset($result);


				if ($this->rename($file, dirname($file) . '/processed/' . basename($file))) {
					$this->log(basename($file) . ' was sucessfully imported and archived', array($file), 1);
				} else {
					$this->log(basename($file) . ' was imported but could not be archived', array(
						'source' => $file,
						'target' => dirname($file) . '/processed/' . basename($file)
					), 3);
				}

				if (count($this->missing_files) > 0) {
					$missing_file = dirname($file) . '/missing/missing_' . time() . '.json';
					$data = new stdClass();
					$data->count = count($this->missing_files);
					$data->data = (object)$this->missing_files;
					file_put_contents($missing_file, json_encode($data));
				}
			}
			$this->processed[] = $file;
		}

		$this->recheckMissingFiles();

		$this->log('No files to import ', array('path' => $this->hotfolder, 'files' => $files), 0);
		return FALSE;
	}

	public function takeAction($data) {
		foreach ($data as $key => $item) {
			try {
				if (property_exists($item, 'import_next_try') && $item->import_next_try > time()) {
					$this->missing_files[] = $item;
					continue;
				}

				switch ($item->sync_action) {
					case 'update':
						if (in_array($item->data_mimetype, $this->ignoredMimeTypes)) {
							continue;
						}

						$damObject = DamModel::getByMamUID($item->data_id);
						$action = 'Updated';
						if ($damObject === FALSE) {
							$damObject = t3lib_div::makeInstance('DamModel');
							$action = 'Inserted';
						}
						$damObject->pid = $this->media_pid;
						$damObject->logging = $this;
						$damObject->mediafolder = str_replace(PATH_site, '', $this->mediafolder);
						$damObject->import($item);

						$file = (PATH_site . $damObject->file_path . $damObject->file_name);

							// Check if the File has a wrong filename encoding
						if (!file_exists($file) && file_exists(utf8_decode($file))) {
							rename(utf8_decode($file), $file);
						}

						if (!file_exists($file)) {
							$damObject->file_path = $file;
							$this->stat['files_missing']++;

							if (!property_exists($item, 'import_date')) {
								$item->import_date = time();
							}
								// The older the Import gets the more unlikely it is that it will come back
								// So the interval to check this file will be doubled on each try
								// 1min -> 2min -> 4min -> 8min -> 16min ...
								// if (!property_exists($item, "import_next_try"))
								// 	$time = (60 * 1);
								// else
								// 	$time = (($item->import_next_try - $item->import_date) * 2);

							$item->import_next_try = time() + (60 * 5);

							$this->log('File does not exist: ' . $item->data_name . ' | Rechecking in: 5 min', array(
								'file' => $file,
								'damObject' => $damObject
							), 3);

							$this->missing_files[] = $item;
						} else {
							$damObject->save(TRUE, TRUE);
							$this->log($action . ': ' . $item->data_name . ' into [pid:' . $damObject->pid . ']', $damObject, 1);
							$this->stat['files_' . strtolower($action)]++;
							$this->found_files[] = $item;
						}
						unset($damObject);
						break;

					case 'delete':
						$object = DamModel::getByMamUID($item->data_id);
						$object->logging = $this;
						if (is_object($object) && method_exists($object, 'save')) {
							if (strlen($object->tx_dammam_mamuuid) > 0) {
								$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_dammam_unresolved_relations', 'uuid_local="' . $object->tx_dammam_mamuuid . '"');
							}
							$object->deleted = 1;
							$object->save(TRUE, TRUE);
							$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_dam', 'tx_dammam_mamuuid="' . $item->data_id . '"', array('deleted' => '1'));
							$this->log('Deleting: ' . $item->data_name, $object, 1);
						} else {
							$this->log('The Asset you\'re trying to delete doesn\'t exist: ' . $item->data_name);
						}

						$this->stat['files_deleted']++;
						break;

					default:
						$this->log('Unknown sync action', $item, 2);
						break;
				}
			} catch (Exception $e) {
				$this->log('There was an unexpected Error: ' . $e->getMessage(),
					array(
						'Exception' => $this->objectToArray($e)
					), 3);
			}
		}
	}

	public function recheckMissingFiles() {
		$files = scandir($this->hotfolder . 'missing/');

		if (count($files) < 1) {
			return;
		}

		$this->log('Checking for Missing files: ' . basename($file), array($files), 0);
		$counter = 0;
		foreach ($files as $file) {
			if (substr($file, 0, 1) == '.') {
				continue;
			}

			if (!stristr($file, 'missing')) {
				$this->log('Skipping: ' . basename($file), array(), 0);
				continue;
			}

			if ($counter >= $this->max) {
				return;
			}
			$counter++;

			$file = $this->hotfolder . 'missing/' . $file;

			if ($file) {
				$this->log('Loading: ' . basename($file), array(), 0);

				$content = file_get_contents($file);
				if (empty($content)) {
					$this->log('The file ' . basename($file) . ' is empty.',
						array(), 1);
					$this->rename($file, dirname($file) . '/failed/' . basename($file));
					continue;
				}

				$result = json_decode($content);
				if (!is_a($result, 'stdClass')) {
					$this->log('The file ' . basename($file) . ' could not be loaded',
						array(
							'Error:' => 'The file "' . $file . '" can not be read or has syntax errors.',
							'Hint:' => 'Please check that the apache user has rights to the read the file and that it is a valid json document'
						), 3);
					$this->rename($file, dirname($file) . '/failed/' . basename($file));
					continue;
				}

				$this->takeAction($result->data);
				unset($result);

				unlink($file);

				if (count($this->found_files) > 0) {
					$found_file = dirname($file) . '/../processed/' . basename($file);
					$data = new stdClass();
					$data->count = count($this->found_files);
					$data->data = (object)$this->found_files;
					file_put_contents($found_file, json_encode($data));

					$this->log('Found some missing files', array(
						'missing_file' => $found_file,
						'still missing:' => $this->found_files
					), 1);
				}

				if (count($this->missing_files) > 0) {
					$missing_file = dirname($file) . '/missing_' . time() . '.json';
					$data = new stdClass();
					$data->count = count($this->missing_files);

					$uniqueFiles = array();
					foreach ($this->missing_files as $file) {
						$date = new DateTime($item->data_modification_date);
						$file->modification_timestamp = $date->getTimestamp();
						if (!isset($uniqueFiles[$file->data_id])) {
							$uniqueFiles[$file->data_id] = $file;
						} elseif ($date->getTimestamp() > $uniqueFiles[$file->data_id]->modification_timestamp) {
							$uniqueFiles[$file->data_id] = $file;
						}
					}

					$data->data = (object) $uniqueFiles;
					file_put_contents($missing_file, json_encode($data));

					$this->log('Not all missing files could be found', array(
						'missing_file' => $missing_file,
						'still missing:' => $uniqueFiles
					), 1);
				}
			}
			$this->processed[] = $file;
		}
	}

	public function loadConf() {
		$confFile = $this->hotfolder . '/conf.json';
		if (!file_exists($confFile)) {
			$this->log('The Synchronisation config file does not exist.',
				array(
					'Error:' => 'The file "' . $confFile . '" does not exist.',
					'Hint:' => 'Please make sure that you have set up the right hotfolder in the Scheduler.'
				), 3);
			return FALSE;
		}

		$GLOBALS['DAM_MAM']['conf'] = json_decode(file_get_contents($confFile));
		if (!is_a($GLOBALS['DAM_MAM']['conf'], 'stdClass')) {
			$this->log('The Synchronisation config could not be loaded.',
				array(
					'Error:' => 'The file "' . $confFile . '" can not be read or has syntax errors.',
					'Hint:' => 'Please check that the apache user has rights to the read the file and that it is a valid json document'
				), 3);
			return FALSE;
		}
		return TRUE;
	}

	public function rename($from, $to) {
		$this->log('Moving File: ' . basename($from),
			array(
				'From:' => $from,
				'To:' => $to
			), 0);
		if (!stristr($from, '.json') && !$from == 'missing') {
			$this->log('Tried to move Media file!',
				array(
					'From:' => $from,
					'To:' => $to
				), 3);
			return;
		}
		if (file_exists($from) && is_file($from)) {
			return rename($from, $to);
		}
		return 0;
	}

	protected $assetIndex = array();
	public function cleanUpAbandonedFiles($path) {
		if (empty($this->assetIndex)) {
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_dam', '1=1');
			foreach ($rows as $row) {
				$file = PATH_site . $row['file_path'] . $row['file_name'];
				if (!isset($this->assetIndex[$file])) {
					$this->assetIndex[$file] = $row;
				} elseif ($this->assetIndex[$file]['tstamp'] < $row['tstamp']) {
						// $this->log('Newer Entry' . $row['file_name'], array(
						// 	date('d.m.y H:i:s', $this->assetIndex[$file]['tstamp']) . '<' . date('m.d.y H:i:s', $row['tstamp']),
						// 	$this->assetIndex[$file]['tx_dammam_mamuuid'] . ' == ' . $row['tx_dammam_mamuuid'],
						// 	$this->assetIndex[$file]['deleted'] . ' == ' . $row['deleted'],
						// ));
					$this->assetIndex[$file] = $row;
				}
			}
		}

		$subPaths = scandir($path);
		foreach ($subPaths as $name) {
			if ($name == '.' || $name == '..' || $name == '.trash') {
				continue;
			}
			$subPath = $path . $name;
			if (is_dir($subPath)) {
				$this->cleanUpAbandonedFiles($subPath . '/');
			} else {
				if (!isset($this->assetIndex[$subPath]) || $this->assetIndex[$subPath]['deleted']) {
					$lastModification = filemtime($subPath);
					if ($lastModification < (time() - (60 * 60 * 12))) {
						$this->log('Deleting abandoned File: ' . $name, array(
							'Path' => $subPath,
							'Last modification' => date('d.m.y H:i:s', filemtime($subPath)),
							'Asset' => isset($this->assetIndex[$subPath]) ? $this->assetIndex[$subPath] : array()
						), 1, TRUE);
						if (is_file($subPath) && !is_dir($subPath)) {
							unlink($subPath);
						}
					} else {
						// $this->log('File was recently modified: ' . $name . ' : ' . date('d.m.y H:i:s', filemtime($subPath)), $this->assetIndex[$subPath]);
					}
				} else {
					// $this->log('Tracked: ' . $name, $this->assetIndex[$subPath]);
				}
			}
		}
	}

	public function cleanUpAbandonedFolders($path) {
		$subPaths = scandir($path);
		$containsFiles = FALSE;
		foreach ($subPaths as $name) {
			if ($name == '.' || $name == '..' || $name == '.trash') {
				continue;
			}
			$subPath = $path . $name;
			if (is_dir($subPath)) {
				$containsFiles = $this->cleanUpAbandonedFolders($subPath . '/') ? TRUE : $containsFiles;
			} else {
				$containsFiles = TRUE;
			}
		}

		$stat = stat($path);
		if (!$containsFiles && count($subPaths) == 2) {
			if ($stat['mtime'] < ( time() - ( 60 * 30 ) )) {
				$this->log('Deleting empty Folder: ' . basename($path), array(
					'Path' => $path,
					'Last modification' => date('H:i:s d.m.Y', $stat['mtime']),
					'Files' => $subPaths
				), 1, TRUE);
				rmdir($path);
			} else {
				$this->log('Found empty Folder which has been modified in the last 30 min: ' . basename($path), array(
					'Path' => $path,
					'Last modification' => date('H:i:s d.m.Y', $stat['mtime'])
				), 1);
			}
		}

		return $containsFiles;
	}

	public function redoRelations() {
		$relations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_dammam_unresolved_relations', '1=1');
		foreach ($relations as $relation) {
			if (!stristr($relation['uuid_foreign'], 'data_')) {
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_dammam_unresolved_relations', 'uuid_local="' . $relation['uuid_local'] . '" AND crdate = "' . $relation['crdate'] . '"');
			}
		}

		$assets = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_dam', 'deleted=0');
		foreach ($assets as $asset) {
			preg_match("/((?P<value20>\d{2}\.\d+\.[A-Za-z]{2}[_-])(.*)|(?P<value25>\d.+[_])(.*)(?=[_-][A-Z]{2}[\d\._])(.*))(?P<value99>.*$)/", $asset['file_name'], $matches);
			if (isset($matches['value20']) || isset($matches['value25'])) {
				$group = strlen($matches['value20']) > 0 ? $matches['value20'] : $matches['value25'];
				$values = array(
					'crdate' => time(),
					'uuid_local' => $asset['tx_dammam_mamuuid'],
					'uuid_foreign' => $group
				);
				$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dammam_unresolved_relations', $values);
			}
		}
	}
}

?>
