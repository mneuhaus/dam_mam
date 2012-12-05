<?php

/**
*
*/
class DamModel extends BaseModel {
	var $table = "tx_dam";
	var $hashKey = "file_path";
	var $mappings = array(
		"tx_dammam_mamuuid" 		=> "data_id",
		"title"						=> "data_name",
		"file_type"					=> "data_name",
		"file_name"					=> "derivate_name",
		"media_type"				=> "derivate_mimetype",
		"file_mime_type"			=> "derivate_mimetype",
		"file_mime_subtype"			=> "derivate_mimetype",
		"tstamp"					=> "data_modification_date",
		"file_path"					=> "data_shellpath",
		"file_size"					=> "derivate_name",
		"file_dl_name"				=> "data_name",
		"hpixels"					=> "data_shellpath",
		"vpixels"					=> "data_shellpath",
		"color_space"				=> "file_property.image.colorspace",
		"crdate" 					=> "data_file_creation_date",

		"tx_dammam_mime_type"		=> "data_mimetype",
		"tx_dammam_file_type"		=> "data_name",
		"tx_dammam_file_name"		=> "data_name",
		"tx_dammam_color_space"		=> "file_property.image.colorspace",
		"tx_dammam_hpixels"			=> "data_width",
		"tx_dammam_vpixels"			=> "data_height",
	);

	/**
	 * @var array
	 */
	protected $properties = array(
		"haecker_kategorie" => "Haecker",
		"meica_kategorie" => "Meica",
		"meica_produktmarke" => "Produktmarke",
		"Ruegenwalder_Produktgruppe" => "Ruegenwalder",
		"auswahl" => "Auswahl",
		"usageRight" => "Nutzungsrecht",
		"schlagwortliste" => "Schlagwörter"
	);

	var $index_type = "man";

	public function save($handleRelatedFiles = true){
		$values = get_object_vars($this);
		if(!isset($values["uid"])){
			$uid = tx_dam_db::insertRecordRaw($values);
			$values["uid"] = $uid;
		}

		tx_dam_db::insertUpdateData($values);

		if(!empty($values["file_type"])){
			$dbTrigger = t3lib_div::makeInstance('tx_dam_dbTriggerMediaTypes');
			$dbTrigger->insertMetaTrigger($values);
		}


		if (!empty($values['tx_dammam_related_files']) && $handleRelatedFiles) {
			#$this->logging->log("Updated related files reversely for:" . $values["title"], array(
			#	"related files" => $values['tx_dammam_related_files'],
			#	"this" => $this
			#), 0);

			$relatedFiles = explode(",", $values['tx_dammam_related_files']);
			foreach ($relatedFiles as $relatedFile) {
				$damObject = DamModel::getByUID($relatedFile);
				if (is_object($damObject)) {
					$uids = explode(",", $damObject->tx_dammam_related_files);
					$uids[] = $values["uid"];
					$damObject->tx_dammam_related_files = trim(implode(",", array_unique($uids)), ",");
					$damObject->save(false);

					#$this->logging->log("Updated related files for:" . $damObject->title, array(
					#	"damObject" => $damObject
					#), 0);
				}
			}
		}

		$manualUpdates = array(
			"sys_language_uid" => $this->sys_language_uid,
			"pid" => $this->pid,
			"tx_dammam_language" => $this->tx_dammam_language,
			"tx_dammam_related_files" => $this->tx_dammam_related_files
		);
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->table, "uid=" . $values["uid"], $manualUpdates);


		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_dam_mm_cat', 'uid_local="'.$values["uid"].'"');
		$categories = explode(",", $values["category"]);
		foreach ($categories as $category) {
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dam_mm_cat', array(
				"uid_local" => $values["uid"],
				"uid_foreign" => $category
			));
		}

	}

	public function media_type($value){
		$parts = explode("/", $value);
		$type = $parts[0];
		return tx_dam::convert_mediaType($type);
	}

	public function title($value){
		return basename($value, "." . pathinfo($value, PATHINFO_EXTENSION));
	}

	public function file_mime_type($value){
		$parts = explode("/", $value);
		return $parts[0];
	}

	public function file_mime_subtype($value){
		$parts = explode("/", $value);
		array_shift($parts);
		return implode("/", $parts);
	}

	public function file_type($value){
		return pathinfo($value, PATHINFO_EXTENSION);
	}

	public function file_size($value){
		$file = PATH_site . $this->file_path . $this->file_name;

		if(file_exists($file))
			return filesize($file);

		return 0;
	}

	public function tx_dammam_file_type($value){
		return $this->file_type($value);
	}

	public function tx_dammam_language($properties) {
		$mappings = $GLOBALS["DAM_MAM"]["conf"]->language_mapping;
		$this->logging->log("Language Mappings", array(
			"mappings" => $mappings,
			"properties" => $properties
		), 0);
		$languages = array();
		foreach ($mappings as $mapping) {
			$fields = get_object_vars($mapping->fields);
			$matches = True;
			$propertyValues = array();
			foreach ($fields as $field => $search) {
				if (isset($properties[$field])){
					$values = $this->callMAMConverter($properties[$field]);
					$propertyValues[$field] = $values;

					if (!is_array($values)) {
						$values = array( $values );
					}
					if (!in_array($search, $values)) {
						$matches = False;
					}
				} else {
					$matches = False;
				}
			}

			if ($matches) {
				$language = $mapping->sys_language_uid;
				$languages[] = $language;
				$this->logging->log("Language matched to :" . $language, array(
					"fields" => $fields,
					"values" => $propertyValues
				), 0);
			}
		}
		return implode(",", array_unique($languages));
	}

	public function tx_dammam_related_files($value) {
		$uids = array();
		if (strlen($value) > 0){
			if (stristr($value, ";")){
				$mids = explode(";", $value);
			}else {
				$mids = explode(",", $value);
			}
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_dammam_unresolved_relations', 'uuid_local="'.$this->tx_dammam_mamuuid.'"');
			foreach ($mids as $mid) {
				$object = DamModel::getByMamUID($mid);
				if(is_object($object) && intval($object->uid) > 0) {
					$uids[] = $object->uid;
				} else {
					preg_match("/((?P<value20>\d{2}\.\d+\.[A-Za-z]{2}[_-])(.*)|(?P<value25>\d.+[_])(.*)(?=[_-][A-Z]{2}[\d\._])(.*))(?P<value99>.*$)/", $mid, $matches);
					if (isset($matches["value20"]) || isset($matches["value25"])) {
						$values = array(
							'crdate' => time(),
							'uuid_local' => $this->tx_dammam_mamuuid,
							'uuid_foreign' => isset($matches["value20"]) > 0 ? $matches["value20"] : $matches["value25"]
						);
						$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dammam_unresolved_relations', $values);
					}
					#$this->logging->log("Relation not found", array(
					#	"mid" => $mid
					#));
				}

				$values = array(
					'crdate' => time(),
					'uuid_local' => $this->tx_dammam_mamuuid,
					'uuid_foreign' => $mid
				);
				$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dammam_unresolved_relations', $values);
			}
			$this->logging->log("Related Files for: " . $this->file_name,
				array(
					"MIDS:" => $mids,
					"Values:"=> $value,
					"UIDS:" => $uids
				), 0);
		}
		return implode(",", $uids);
	}

	public function addRelatedFile($uid) {
		$uids = explode(",", $this->tx_dammam_related_files);
		$uids[] = $uid;
		$this->tx_dammam_related_files = trim(implode(",", $uids), ",");
	}

	public function hpixels($value){
		$file = PATH_site . $this->file_path . $this->file_name;
		if(file_exists($file)){
			return @imagesy($file);
		}
	}

	public function vpixels($value){
		$file = PATH_site . $this->file_path . $this->file_name;
		if(file_exists($file)){
			if(@getimagesize($file) == false){
                $this->logging->log("The processed file was no image",
					array(
						"File:" => $file
					), 2);
				return "0";
			}
			return @imagesx($file);
		}
	}

	public function addCategory($values, $categories = array(), $parent = 0){
		if(is_array($values)){
			foreach ($values as $key => $value) {
				$category = DamCategoryModel::getByName($value);
				if($category === false){
					$category = t3lib_div::makeInstance("DamCategoryModel");
					$category->title = $value;
				}
				$category->pid = $this->pid;
				$category->parent_id = $parent;
				$category->save();
				$categories[] = $category->uid;
			}
		}
		return $categories;
	}

	static public function getByMamUID($uid){
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows("*", "tx_dam", "tx_dammam_mamuuid = '".$uid."' && deleted=0");
		if(count($rows) > 0){
			$row = current($rows);
			$object = t3lib_div::makeInstance("DamModel");
			foreach ($row as $key => $value) {
				$object->$key = $value;
			}

			$categoriesRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows("*", "tx_dam_mm_cat", "uid_local = '".$row["uid"]."'");
			$categories = array();
			foreach($categoriesRows as $categoryRow) {
				$categories[] = $categoryRow["uid_foreign"];
			}
			$object->category = implode(",", $categories);

			return $object;
		}

		return false;
	}

	static public function getByUID($uid){
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows("*", "tx_dam", "uid = '".$uid."' && deleted=0");
		if(count($rows) > 0){
			$row = current($rows);
			$object = t3lib_div::makeInstance("DamModel");
			foreach ($row as $key => $value) {
				$object->$key = $value;
			}

			$categoriesRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows("*", "tx_dam_mm_cat", "uid_local = '".$uid."'");
			$categories = array();
			foreach($categoriesRows as $categoryRow) {
				$categories[] = $categoryRow["uid_foreign"];
			}
			$object->category = implode(",", $categories);

			return $object;
		}

		return false;
	}
}


?>