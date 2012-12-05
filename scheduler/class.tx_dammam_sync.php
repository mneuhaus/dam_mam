<?php

/**
 *
 */
class tx_dammam_sync extends tx_scheduler_Task
{
	protected $moveProcessedFiles = True;

	/**
	 * @var array
	 */
	protected $processed = array();

	/**
	 * @var boolean
	 */
	protected $import_missing = false;

	/**
	 *
	 * @var array
	 */
	protected $missing_files = array();

	/**
	 * @var array
	 */
	protected $ignoredMimeTypes = array(
		"application/octet-stream"
	);

	/**
	 * @var array
	 */
	protected $stat = array(
		"start" => 0,
		"stop" => 0,
		"files_inserted" => 0,
		"files_updated" => 0,
		"files_deleted" => 0,
		"files_missing" => 0
	);

	public function execute()
	{
		try {
			$this->stat["start"] = function_exists("microtime") ? microtime(true) : time();

			if ($this->loadConf() == false) return false;

			$this->sanitize();

			try {
				$this->process();
			} catch (Exception $e) {
				$this->log("The import failed!", $e, 3);
				return false;
			}

			$this->checkUnresolvedRelations();
			$this->cleanUpAbandonedFolders($this->mediafolder);

			#$object = DamModel::getByMamUID('data_20120911135000_C346401C268CC98A497B14F48FAA61DF');
			#$this->log("blaaa", array($object));
			#$object->save();

			$this->stat["stop"] = function_exists("microtime") ? microtime(true) : time();

			$s = $this->stat;
			$this->log("Import statistics", array(
				"Total time" => ($s["stop"] - $s["start"]),
				"New Files" => $s["files_inserted"],
				"Updated Files" => $s["files_updated"],
				"Deleted Files" => $s["files_deleted"],
				"Missing Files" => $s["files_missing"],
				"Total Files processd" => ($s["files_inserted"] + $s["files_updated"] + $s["files_deleted"] + $s["files_missing"]),
				"File per second" => ($s["files_inserted"] + $s["files_updated"] + $s["files_deleted"] + $s["files_missing"]) / ($s["stop"] - $s["start"]),
				"Processed Files" => $this->processed
			), 1);
		} catch (Exception $e) {
			$this->log("There was an unexpected Error: " . $e->getMessage(),
				array(
					"Exception" => $this->objectToArray($e)
				), 3);
		}

		return true;
	}

	public function log($msg, $data = null, $severity = 0)
	{
		if ($severity >= $this->debuglevel) {
			$data = $this->objectToArray($data);
			t3lib_div::devLog($msg, "dam_mam", $severity, $data);

			$severities = array(
				0 => E_USER_NOTICE,
				1 => E_USER_NOTICE,
				2 => E_WARNING,
				3 => E_WARNING,
				-1 => E_USER_NOTICE
			);

			$additional = "";
			#$additional = "\n" . var_export($data, true);

			$msg = $msg . $additional . "\n\n";
			#error_log($msg, 3, $this->hotfolder . "logs/" . date("d.m.y") . ".log");
			#trigger_error($msg, $severities[$severity]);

			unset($data);
		}
	}

	public function objectToArray($object)
	{
		if (is_object($object))
			$array = get_object_vars($object);
		elseif (is_array($object))
			$array = $object;

		if (is_array($array)) {
			foreach ($array as $key => $value) {
				if (is_object($value))
					$array[$key] = $this->objectToArray($value);
			}
		}
		return $array;
	}

	public function sanitize()
	{
		$this->hotfolder = realpath($this->hotfolder) . "/";
		$this->mediafolder = realpath($this->mediafolder) . "/";
		$this->trashfolder = realpath($this->mediafolder) . "/.trash/";
		$this->max = intval($this->max);
		$this->media_pid = intval($this->media_pid);
	}

	public function process()
	{
		$files = scandir($this->hotfolder);
		$counter = 0;
		foreach ($files as $file) {
			if (substr($file, 0, 1) == ".") continue;

			if (stristr($file, "missing")) {
				$this->rename($file, $this->hotfolder . "/missing/" . basename($file));
			}
			if (!stristr($file, "export")) {
				if (!is_dir($file) && basename($file) !== "conf.json") {
					$this->log("Skipping: " . basename($file), array(), 0);
				}
				continue;
			}

			if ($counter >= $this->max) return;
			$counter++;

			$file = $this->hotfolder . $file;

			if ($file) {
				$this->log("Loading: " . basename($file), array(), 0);

				$content = file_get_contents($file);
				if (empty($content)) {
					$this->log("The file " . basename($file) . " is empty.",
						array(), 1);
					$this->rename($file, dirname($file) . "/failed/" . basename($file));
					continue;
				}

				// convert complex xe+ notated numbers into strings, because json_decode can't handle them
				$content = preg_replace('/"number": *(.+e\+.+?)(,*)/', '"number": "$1"$2', $content);

				$result = json_decode($content);
				if (!is_a($result, "stdClass")) {
					$this->log("The file " . basename($file) . " could not be loaded",
						array(
							"Error:" => "The file '" . $file . "' can not be read or has syntax errors.",
							"Hint:" => "Please check that the apache user has rights to the read the file and that it is a valid json document"
						), 3);
					$this->rename($file, dirname($file) . "/failed/" . basename($file));
					continue;
				}

				$this->takeAction($result->data);
				unset($result);


				#if ($this->moveProcessedFiles) {
				if ($this->rename($file, dirname($file) . "/processed/" . basename($file)))
					$this->log(basename($file) . " was sucessfully imported and archived", array($file), 1);
				else
					$this->log(basename($file) . " was imported but could not be archived", array(
						"source" => $file,
						"target" => dirname($file) . "/processed/" . basename($file)
					), 3);
				#}

				if (count($this->missing_files) > 0) {
					$missing_file = dirname($file) . "/missing/missing_" . time() . ".json";
					$data = new stdClass();
					$data->count = count($this->missing_files);
					$data->data = (object)$this->missing_files;
					file_put_contents($missing_file, json_encode($data));
				}
			}
			$this->processed[] = $file;
		}

		$this->recheckMissingFiles();

		$this->log("No files to import ", array("path" => $this->hotfolder, "files" => $files), 0);
		return false;
	}

	public function takeAction($data)
	{
		foreach ($data as $key => $item) {
			try {
				if (property_exists($item, "import_next_try") && $item->import_next_try > time()) {
					$this->missing_files[] = $item;
					continue;
				}
				#var_dump(property_exists($item, "import_next_try"));
				#var_dump($item->import_next_try - time(), $item->import_next_try > time());

				switch ($item->sync_action) {
					case 'update':
						if (in_array($item->data_mimetype, $this->ignoredMimeTypes)) continue;

						$damObject = DamModel::getByMamUID($item->data_id);
						$action = "Updated";
						if ($damObject === false) {
							$damObject = t3lib_div::makeInstance('DamModel');
							$action = "Inserted";
						}
						$damObject->pid = $this->media_pid;
						$damObject->logging = $this;
						$damObject->mediafolder = str_replace(PATH_site, "", $this->mediafolder);
						$damObject->import($item);

						#					if(!file_exists($damObject->file_name))
						#						$damObject->file_name = pathinfo($damObject->file_name, PATHINFO_FILENAME) . ".jpg";
						#$damObject->deleted = 0;
						$file = (PATH_site . $damObject->file_path . $damObject->file_name);

						// Check if the File has a wrong filename encoding
						if (!file_exists($file) && file_exists(utf8_decode($file))) {
							rename(utf8_decode($file), $file);
						}

						if (!file_exists($file)) {
							$damObject->file_path = $file;
							$this->stat["files_missing"]++;

							if (!property_exists($item, "import_date"))
								$item->import_date = time();

							// The older the Import gets the more unlikely it is that it will come back
							// So the interval to check this file will be doubled on each try
							// 1min -> 2min -> 4min -> 8min -> 16min ...
							if (!property_exists($item, "import_next_try"))
								$time = (60 * 1);
							else
								$time = (($item->import_next_try - $item->import_date) * 2);

							$item->import_next_try = time() + $time;

							$this->log("File does not exist: " . $item->data_name . " | Rechecking in: " . intval(($time / 60)) . "min", array(
								"file" => $file,
								"damObject" => $damObject
							), 3);

							$this->missing_files[] = $item;
						} else {
							$damObject->save();
							$this->log($action . ': ' . $item->data_name . ' into [pid:' . $damObject->pid . ']', $damObject);
							$this->stat['files_' . strtolower($action)]++;
						}
						unset($damObject);
						break;

					case 'delete':
						$object = DamModel::getByMamUID($item->data_id);
						if (is_object($object) && method_exists($object, 'save')) {
							$this->log('Deleting: ' . $item->data_name, $object);
							if (strlen($object->tx_dammam_mamuuid) > 0) {
								$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_dammam_unresolved_relations', 'uuid_local="'.$object->tx_dammam_mamuuid.'"');
							}
							$object->deleted = 1;
							$object->save();
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

	public function recheckMissingFiles()
	{
		$files = scandir($this->hotfolder . "missing/");

		if (count($files) < 1)
			return;

		$this->log("Checking for Missing files: " . basename($file), array($files), 0);
		$counter = 0;
		foreach ($files as $file) {
			if (substr($file, 0, 1) == ".") continue;

			if (!stristr($file, "missing")) {
				$this->log("Skipping: " . basename($file), array(), 0);
				continue;
			}

			if ($counter >= $this->max) return;
			$counter++;

			$file = $this->hotfolder . "missing/" . $file;

			if ($file) {
				$this->log("Loading: " . basename($file), array(), 0);

				$content = file_get_contents($file);
				if (empty($content)) {
					$this->log("The file " . basename($file) . " is empty.",
						array(), 1);
					$this->rename($file, dirname($file) . "/failed/" . basename($file));
					continue;
				}

				$result = json_decode($content);
				if (!is_a($result, "stdClass")) {
					$this->log("The file " . basename($file) . " could not be loaded",
						array(
							"Error:" => "The file '" . $file . "' can not be read or has syntax errors.",
							"Hint:" => "Please check that the apache user has rights to the read the file and that it is a valid json document"
						), 3);
					$this->rename($file, dirname($file) . "/failed/" . basename($file));
					continue;
				}

				$this->takeAction($result->data);
				unset($result);

				if ($this->rename($file, dirname($file) . "/../processed/" . basename($file)))
					$this->log(basename($file) . " was sucessfully imported and archived...", array($file, $this->missing), 1);
				else
					$this->log(basename($file) . " was imported but could not be archived", array(
						"source" => $file,
						"target" => dirname($file) . "/../processed/" . basename($file)
					), 1);

				if (count($this->missing_files) > 0) {
					$missing_file = dirname($file) . "/" . basename($file);
					$data = new stdClass();
					$data->count = count($this->missing_files);
					$data->data = (object)$this->missing_files;
					file_put_contents($missing_file, json_encode($data));
				}
			}
			$this->processed[] = $file;
		}
	}

	public function loadConf()
	{
		$confFile = $this->hotfolder . "/conf.json";
		if (!file_exists($confFile)) {
			$this->log("The Synchronisation config file does not exist.",
				array(
					"Error:" => "The file '" . $confFile . "' does not exist.",
					"Hint:" => "Please make sure that you have set up the right hotfolder in the Scheduler."
				), 3);
			return false;
		}

		$GLOBALS["DAM_MAM"]["conf"] = json_decode(file_get_contents($confFile));
		if (!is_a($GLOBALS["DAM_MAM"]["conf"], "stdClass")) {
			$this->log("The Synchronisation config could not be loaded.",
				array(
					"Error:" => "The file '" . $confFile . "' can not be read or has syntax errors.",
					"Hint:" => "Please check that the apache user has rights to the read the file and that it is a valid json document"
				), 3);
			return false;
		}
		return true;
	}

	function rename($from, $to)
	{
		if (!stristr($from, ".json") && !$from == 'missing') {
			$this->log("Tried to move Media file!",
				array(
					"From:" => $from,
					"To:" => $to
				), 3);
			return;
		}
		if (file_exists($from) && is_file($from)) {
			return rename($from, $to);
		}
		return 0;
	}

	public function checkUnresolvedRelations()
	{
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows("*", "tx_dammam_unresolved_relations", "1=1");
		$this->log("Updating relations", array(	"relations" => $rows     ), 0);
		foreach ($rows as $row) {
			if (empty($row["uuid_foreign"])) continue;

			if (stristr($row["uuid_foreign"], "data_")) {
				$foreignObject = DamModel::getByMamUID($row["uuid_foreign"]);
				$localObject = DamModel::getByMamUID($row["uuid_local"]);
				if (!is_object($localObject)) continue;

				if (is_object($foreignObject) && intval($foreignObject->uid) > 0) {
					$localObject->logging = $this;
					$localObject->addRelatedFile($foreignObject->uid);
					$localObject->save();
					#$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_dammam_unresolved_relations', 'uuid_local="'.$row["uuid_local"].'" AND uuid_foreign="'.$row["uuid_foreign"].'"' );
					#$this->log("Resolved relation", array(
					#	"row" => $row,
					#	"foreignObject" => $foreignObject,
					#	'localObject' => $localObject
					#), 0);
				} else {
					#$this->log("Couldn't resolve relation", array(
					#	"row" => $row,
					#	"foreignObject" => $foreignObject,
					#	'localObject' => $localObject
					#), 0);
				}
			} else {
				$localObject = DamModel::getByMamUID($row["uuid_local"]);

				if (is_object($localObject)) {
					$groupRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows("*", "tx_dammam_unresolved_relations", "uuid_foreign='" . $row["uuid_foreign"] . "'");

					#$this->log("Resolving group relations", array(
					#	"rows" => $groupRows
					#), 0);
					foreach ($groupRows as $groupRow) {
						$foreignObject = DamModel::getByMamUID($groupRow["uuid_local"]);
						if (is_object($foreignObject) && intval($foreignObject->uid) > 0 && $foreignObject->uid !== $localObject->uid) {
							$localObject->logging = $this;
							$localObject->addRelatedFile($foreignObject->uid);
							$localObject->save();
							#$this->log("Resolved group relation", array(
							#	"rows" => $groupRows,
							#	"foreignObject" => $foreignObject,
							#	'localObject' => $localObject
							#), 0);
						}
					}
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
			$subPath = $path . $name ;
			$this->log($subPath);
			if (is_dir($subPath)) {
				$containsFiles = $this->cleanUpAbandonedFolders($subPath . '/');
				if ($containsFiles) {
					$containsFiles = TRUE;
				} else {
					$this->log('Deleting empty Folder: ' . $name, array(
						"Path" => $subPath
					));
					rmdir($subPath);
				}
			} else {
				return TRUE;
			}
		}
		return $containsFiles;
	}
}

?>