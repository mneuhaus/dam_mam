<?php

/**
*
*/
class BaseModel {
	var $mappings = array();
	var $hashKey = "tstamp";
	var $pid = 73;

	public function save(){
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;

		$values = get_object_vars($this);
		unset($values["hashKey"]);
		unset($values["mappings"]);
		unset($values["table"]);

		if(isset($values["uid"])){
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->table, "uid=" . $values["uid"], $values);
		}else{
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($this->table, $values);
			$this->uid = $GLOBALS['TYPO3_DB']->sql_insert_id($res);
		}

		return $this->uid;
	}

	public function load(){
	}

	public function import($data){
		$unusedProperties = array_keys(get_object_vars($data->properties));

		$mappings = array_merge($this->mappings, get_object_vars($GLOBALS["DAM_MAM"]["conf"]->property_mapping));
		foreach ($mappings as $damField => $mamField) {
			if(property_exists($data, $mamField)){
				$value = $data->$mamField;
				if(method_exists($this, $damField)){
					$value = call_user_func(array($this, $damField), $value);
				}
				$this->$damField = $value;
			}

			if(property_exists($data->properties, $mamField)){
				unset($unusedProperties[array_search($mamField, $unusedProperties)]);

				$value = $data->properties->$mamField;

				$mamPropertyType = str_replace("com.crossmedia_solutions.mam.vo.", "", $value->class);
				$value = call_user_func(array($this, $mamPropertyType), $value);

				if(method_exists($this, $damField)){
					$value = call_user_func(array($this, $damField), $value);
				}
				$this->$damField = $value;
			}
		}

		$categories = array();
		$properties = get_object_vars($GLOBALS["DAM_MAM"]["conf"]->category_properties);
		foreach ($properties as $mamField => $mamLabel) {
			if(property_exists($data->properties, $mamField)){
				unset($unusedProperties[array_search($mamField, $unusedProperties)]);
				$value = $data->properties->$mamField;
				$value = $this->callMAMConverter($value);
				if(!empty($value)){
					$category = DamCategoryModel::getByName($mamLabel);
					if($category === false){
						$category = t3lib_div::makeInstance("DamCategoryModel");
						$category->title = $mamLabel;
						$category->save();
					}
					if(!is_array($value))
						$value = array("0" => $value);
					$categories = $this->addCategory($value, $categories, $category->uid);

					$this->logging->log("Adding ".$this->file_name. " to categories " . implode(", ", $value), null, -1);
				}
			}
		}
		$this->category = implode(",", $categories);

		$extraData = "";
		foreach ($unusedProperties as $mamField) {
			$value = $this->callMAMConverter($data->properties->$mamField);
			if (is_array($value)) {
				$value = implode(",", $value);
			}
			if(!empty($value) && !is_object($value) && strval($value)){
				$extraData.= $mamField . ": " . $value . "\n";
			}
		}
		$this->meta = $extraData;
#		return $unusedProperties;

		$this->tx_dammam_language = $this->tx_dammam_language(get_object_vars($data->properties));
	}

	public function tstamp($value){
		return strtotime($value);
	}

	public function crdate($value){
		return strtotime($value);
	}

	public function file_path($value){
		if(isset($GLOBALS["DAM_MAM"]["conf"]->mountpoint_relink_from)){
			$oldValue = $value;
			$value = str_replace($GLOBALS["DAM_MAM"]["conf"]->mountpoint_relink_from, $GLOBALS["DAM_MAM"]["conf"]->mountpoint_relink_to, $value);
			$this->logging->log("Relinking Mountpoint.", array(
				"from:" => $GLOBALS["DAM_MAM"]["conf"]->mountpoint_relink_from,
				"to: " => $GLOBALS["DAM_MAM"]["conf"]->mountpoint_relink_to,
				"value before" => $oldValue,
				"value after" => $value
			), 1);
		}
		return rtrim($this->mediafolder, "/") . $value;
	}

	public function callMAMConverter($data){
		$parts = explode(".", $data->class);
		$mamPropertyType = array_pop($parts);
		if(method_exists($this, $mamPropertyType)){
 			return call_user_func(array($this, $mamPropertyType), $data);
 		}
 		return $data;
	}

	public function MediaAssetStringPropertyVO($data){
		return $data->value->text;
	}

	public function MediaAssetNumberPropertyVO($data){
		return $data->value;
	}

	public function MediaAssetListPropertyVO($data){
		if(!empty($data->value)){
			if(property_exists($data->value, "class")){
				return $this->callMAMConverter($data->value);
			}
		}else{
			return null;
		}
	}

	public function MetaData_ValueType_ListValueVO($data){
		if(!empty($data->value)){
			$array = get_object_vars($data->value);
			foreach($array as $key => $value) {
				if(property_exists($value, "class"))
					$array[$key] = $this->callMAMConverter($value);
			}
			return $array;
		}else{
			return array();
		}
	}
}


?>