<?php

class tx_dammam_garbage_collector extends tx_scheduler_Task {

	public function execute() {
		try {
			$processedFolder = realpath($this->hotfolder) . '/processed/';
			$files = scandir($processedFolder);

			$deleted = array();
			foreach ($files as $file) {
				$path = $processedFolder . $file;
				if (stristr($file, 'export_') || stristr($file, 'missing_')) {
					$deleted[] = $file;
					unlink($path);
				}
			}
			if (count($deleted) > 0) {
				$this->log('Deleted old processed import files', $deleted);
			}
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
}

?>