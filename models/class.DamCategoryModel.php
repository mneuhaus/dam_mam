<?php

/**
* 
*/
class DamCategoryModel extends BaseModel {
	var $table = "tx_dam_cat";
	
	static public function getByName($name){
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows("*", "tx_dam_cat", "title = '".$name."'");
		
		if(count($rows) > 0){
			$row = current($rows);
			$object = t3lib_div::makeInstance("DamCategoryModel");
			foreach ($row as $key => $value) {
				$object->$key = $value;
			}
			return $object;
		}
			
		return false;
	}
}


?>