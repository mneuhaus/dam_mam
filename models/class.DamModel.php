<?php

/**
*
*/
class DamModel extends BaseModel {
	public $table = 'tx_dam';
	public $hashKey = 'file_path';
	public $mappings = array(
		'tx_dammam_mamuuid' 		=> 'data_id',
		'title'						=> 'data_name',
		'file_type'					=> 'data_name',
		'file_name'					=> 'derivate_name',
		'media_type'				=> 'derivate_mimetype',
		'file_mime_type'			=> 'derivate_mimetype',
		'file_mime_subtype'			=> 'derivate_mimetype',
		'tstamp'					=> 'data_modification_date',
		'file_path'					=> 'data_shellpath',
		'file_size'					=> 'derivate_name',
		'file_dl_name'				=> 'data_name',
		'hpixels'					=> 'data_shellpath',
		'vpixels'					=> 'data_shellpath',
		'color_space'				=> 'file_property.image.colorspace',
		'crdate' 					=> 'data_file_creation_date',

		'tx_dammam_mime_type'		=> 'data_mimetype',
		'tx_dammam_file_type'		=> 'data_name',
		'tx_dammam_file_name'		=> 'data_name',
		'tx_dammam_color_space'		=> 'file_property.image.colorspace',
		'tx_dammam_hpixels'			=> 'data_width',
		'tx_dammam_vpixels'			=> 'data_height',
	);

	/**
	 * @var array
	 */
	public $properties = array(
		'haecker_kategorie' => 'Haecker',
		'meica_kategorie' => 'Meica',
		'meica_produktmarke' => 'Produktmarke',
		'Ruegenwalder_Produktgruppe' => 'Ruegenwalder',
		'auswahl' => 'Auswahl',
		'usageRight' => 'Nutzungsrecht',
		'schlagwortliste' => 'Schlagwörter'
	);

	public $index_type = 'man';

	public function save($handleRelatedFiles = TRUE, $logQuery = FALSE) {
		$start = function_exists('microtime') ? microtime(TRUE) : time();

		$values = get_object_vars($this);
		if (!isset($values['uid'])) {
			$this->log('Inserting new Record: ' . $values['title'], $values);
			$uid = tx_dam_db::insertRecordRaw($values);
			$values['uid'] = $uid;
		}

		tx_dam_db::insertUpdateData($values);

		if (!empty($values['file_type'])) {
			$dbTrigger = t3lib_div::makeInstance('tx_dam_dbTriggerMediaTypes');
			$dbTrigger->insertMetaTrigger($values);
		}

		$uids = explode(',', $this->tx_dammam_related_files);
		$this->tx_dammam_related_files = trim(implode(',', array_unique($uids)), ',');

		if (!empty($values['tx_dammam_related_files']) && $handleRelatedFiles) {
			$this->log("Updated related files reversely for:" . $values["title"], array(
				"related files" => $values['tx_dammam_related_files'],
				"this" => $this
			), 0);

			$relatedFiles = explode(',', $values['tx_dammam_related_files']);
			foreach ($relatedFiles as $relatedFile) {
				$damObject = DamModel::getByUID($relatedFile);
				if (is_object($damObject)) {
					$uids = explode(',', $damObject->tx_dammam_related_files);
					if (!in_array($values['uid'], $uids)) {
						$uids[] = $values['uid'];
						$damObject->tx_dammam_related_files = trim(implode(',', array_unique($uids)), ',');
						$damObject->save(FALSE);
					}

					// $this->log("Updated related files for:" . $damObject->title, array(
					// 	"damObject" => $damObject
					// ), 0);
				}
			}
		}

		$values = get_object_vars($this);
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'tx_dam', 'tx_dammam_mamuuid="' . $values['tx_dammam_mamuuid'] . '" AND deleted = 0');
		$values['uid'] = $row['uid'];
		unset($row['uid']);
		$manualUpdates = array();
		foreach (array_keys($row) as $key) {
			if (isset($values[$key])) {
				$manualUpdates[$key] = $values[$key];
			}
		}
		if (intval($values['uid']) > 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->table, 'uid=' . $values['uid'], $manualUpdates);
		}

		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_dam_mm_cat', 'uid_local="' . $values['uid'] . '"');
		$categories = explode(',', $values['category']);
		foreach ($categories as $category) {
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dam_mm_cat', array(
				'uid_local' => $values['uid'],
				'uid_foreign' => $category
			));
		}

			// Integrety check
		$databaseState = DamModel::getByUID($values['uid']);
		if (is_object($databaseState)) {
			$databaseValues = get_object_vars($databaseState);
			$differences = array();
			foreach ($databaseValues as $key => $value) {
				if (in_array($key, array('tstamp', 'category'))) {
					continue;
				}
				if ($values[$key]) {
					if ($values[$key] != $value) {
						$differences = array(
							'Field' => $key,
							'Database' => is_object($value) ? '[object]' : $value,
							'New Value' => is_object($values[$key]) ? '[object]' : $values[$key],
						);
					}
				}
			}
			if (count($differences) > 0) {
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');

				$this->log('Unsaved changes detected: ' . $values['title'], array(
					'Differences' => $differences,
					'Error Log' => $tce->errorLog
				), 3);
			}
		}

		$stop = function_exists('microtime') ? microtime(TRUE) : time();
		$GLOBALS['DAM']['Relation Saving times'][] = $stop - $start;
		$GLOBALS['DAM']['Relation Saving median'] = array_sum($GLOBALS['DAM']['Relation Saving times']) / count($GLOBALS['DAM']['Relation Saving times']);
		if ($handleRelatedFiles) {
			$GLOBALS['DAM']['Saving Count for handleRealtedFiles=true'][$values['tx_dammam_mamuuid']]++;
		} else {
			$GLOBALS['DAM']['Saving Count for handleRealtedFiles=false'][$values['tx_dammam_mamuuid']]++;
		}
		$this->log('Total time spent on updating relations: ' . ($stop - $start), array(
			'Relation Saving median' => $GLOBALS['DAM']['Relation Saving median'],
			'Saving Count for handleRealtedFiles=true' => $GLOBALS['DAM']['Saving Count for handleRealtedFiles=true'],
			'Saving Count for handleRealtedFiles=false' => $GLOBALS['DAM']['Saving Count for handleRealtedFiles=false'],
			'Count' => count($GLOBALS['DAM']['Relation Saving times'])
		), -1);
	}

	public function media_type($value) {
		$parts = explode('/', $value);
		$type = $parts[0];
		return tx_dam::convert_mediaType($type);
	}

	public function title($value) {
		return basename($value, '.' . pathinfo($value, PATHINFO_EXTENSION));
	}

	public function file_mime_type($value) {
		$parts = explode('/', $value);
		return $parts[0];
	}

	public function file_mime_subtype($value) {
		$parts = explode('/', $value);
		array_shift($parts);
		return implode('/', $parts);
	}

	public function file_type($value) {
		return pathinfo($value, PATHINFO_EXTENSION);
	}

	public function file_size($value) {
		$file = PATH_site . $this->file_path . $this->file_name;

		if (file_exists($file)) {
			return filesize($file);
		}
		return 0;
	}

	public function tx_dammam_file_type($value) {
		return $this->file_type($value);
	}

	public function tx_dammam_language($properties) {
		$mappings = $GLOBALS['DAM_MAM']['conf']->language_mapping;
		$this->log('Language Mappings', array(
			'mappings' => $mappings,
			'properties' => $properties
		), 0);
		$languages = array();
		foreach ($mappings as $mapping) {
			$fields = get_object_vars($mapping->fields);
			$matches = TRUE;
			$propertyValues = array();
			foreach ($fields as $field => $search) {
				if (isset($properties[$field])) {
					$values = $this->callMAMConverter($properties[$field]);
					$propertyValues[$field] = $values;

					if (!is_array($values)) {
						$values = array( $values );
					}
					if (!in_array($search, $values)) {
						$matches = FALSE;
					}
				} else {
					$matches = FALSE;
				}
			}

			if ($matches) {
				$language = $mapping->sys_language_uid;
				$languages[] = $language;
				$this->log('Language matched to :' . $language, array(
					'fields' => $fields,
					'values' => $propertyValues
				), 0);
			}
		}
		return implode(',', array_unique($languages));
	}

	public function tx_dammam_related_files($value) {
		$uids = array();
		if (strlen($value) > 0) {
			if (stristr($value, ';')) {
				$mids = explode(';', $value);
			} else {
				$mids = explode(',', $value);
			}
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_dammam_unresolved_relations', 'uuid_local="' . $this->tx_dammam_mamuuid . '"');
			$groups = array();
			foreach ($mids as $mid) {
				$object = DamModel::getByMamUID($mid);
				if (is_object($object) && intval($object->uid) > 0) {
					$uids[] = $object->uid;
				} else {
					preg_match("/((?P<value20>\d{2}\.\d+\.[A-Za-z]{2}[_-])(.*)|(?P<value25>\d.+[_])(.*)(?=[_-][A-Z]{2}[\d\._])(.*))(?P<value99>.*$)/", $mid, $matches);
					if (isset($matches['value20']) || isset($matches['value25'])) {
						$group = strlen($matches['value20']) > 0 ? $matches['value20'] : $matches['value25'];
						$values = array(
							'crdate' => time(),
							'uuid_local' => $this->tx_dammam_mamuuid,
							'uuid_foreign' => $group
						);
						$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dammam_unresolved_relations', $values);
						$groups[] = $group;
					}
				}

				$values = array(
					'crdate' => time(),
					'uuid_local' => $this->tx_dammam_mamuuid,
					'uuid_foreign' => $mid
				);
				$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dammam_unresolved_relations', $values);
			}
			$this->log('Related Files for: ' . $this->file_name,
				array(
					'MIDS:' => $mids,
					'Values:' => $value,
					'UIDS:' => $uids,
					'Groups:' => $group
				), 0);
		}
		return implode(',', $uids);
	}

	public function addRelatedFile($uid) {
		$uids = explode(',', $this->tx_dammam_related_files);
		$uids[] = $uid;
		$this->tx_dammam_related_files = trim(implode(',', $uids), ',');
	}

	public function hpixels($value) {
		$file = PATH_site . $this->file_path . $this->file_name;
		if (file_exists($file)) {
			return @imagesy($file);
		}
	}

	public function vpixels($value) {
		$file = PATH_site . $this->file_path . $this->file_name;
		if (file_exists($file)) {
			if (@getimagesize($file) == FALSE) {
                $this->log('The processed file was no image',
					array(
						'File:' => $file
					), 2);
				return '0';
			}
			return @imagesx($file);
		}
	}

	public function addCategory($values, $categories = array(), $parent = 0) {
		if (is_array($values)) {
			foreach ($values as $key => $value) {
				$category = DamCategoryModel::getByName($value);
				if ($category === FALSE) {
					$category = t3lib_div::makeInstance('DamCategoryModel');
					$category->title = $value;
				}
				$category->pid = $this->pid;
				$category->parent_id = $parent;
				$category->deleted = 0;
				$category->save();
				$categories[] = $category->uid;
			}
		}
		return $categories;
	}

	static public function getByMamUID($uid) {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_dam', 'tx_dammam_mamuuid = "' . $uid . '" && deleted=0');
		if (count($rows) > 0) {
			$row = current($rows);
			$object = t3lib_div::makeInstance('DamModel');
			foreach ($row as $key => $value) {
				$object->$key = $value;
			}

			$categoriesRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_dam_mm_cat', 'uid_local = "' . $row['uid'] . '"');
			$categories = array();
			foreach ($categoriesRows as $categoryRow) {
				$categories[] = $categoryRow['uid_foreign'];
			}
			sort($categories);
			$object->category = implode(',', $categories);

			return $object;
		}

		return FALSE;
	}

	static public function getByUID($uid) {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_dam', 'uid = "' . $uid . '" && deleted=0');
		if (count($rows) > 0) {
			$row = current($rows);
			$object = t3lib_div::makeInstance('DamModel');
			foreach ($row as $key => $value) {
				$object->$key = $value;
			}

			$categoriesRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_dam_mm_cat', 'uid_local = "' . $uid . '"');
			$categories = array();
			foreach ($categoriesRows as $categoryRow) {
				$categories[] = $categoryRow['uid_foreign'];
			}
			sort($categories);
			$object->category = implode(',', $categories);

			return $object;
		}

		return FALSE;
	}

	public function log($msg, $data = NULL, $severity = 0) {
		if ($severity >= $GLOBALS['DebugLevel']) {
			$data = $this->objectToArray($data);
			t3lib_div::devLog($msg, 'dam_mam', $severity, $data);

			$severities = array(
				-1 => 'debug',
				0 => 'debug',
				1 => 'info',
				2 => 'warning',
				3 => 'critical'
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