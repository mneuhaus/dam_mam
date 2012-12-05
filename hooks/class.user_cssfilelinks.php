<?php

require_once(t3lib_extMgm::extPath("dam_filelinks")."/class.tx_damfilelinks.php");

class user_cssfilelinks extends tx_damfilelinks {
	public function fillFileMarkers($fileFileMarkers,$fileLayout,$file,$fileCount,$fileext) {
		if (intval($file["dam"]) > 0) {
			$languages = array();
			foreach ($GLOBALS['TYPO3_DB']->exec_SELECTgetRows("*", 'sys_language', "1=1") as $key => $value) {
				$languages[$value["uid"]] = $value;
			}

			$rows = tx_dam_db::getDataWhere('*', "tx_dam.uid = " . $file["dam"]);
			$row = current($rows);
			$uids = explode(",", $row["tx_dammam_related_files"]);
			
			if(count($uids) > 0){
				$where = array();
				$where['file_name'] = 'tx_dam.uid IN (' . implode(",", $uids) . ')';
				$where['enableFields'] = tx_dam_db::enableFields('tx_dam', '', $mode);
				if ($rows = tx_dam_db::getDataWhere('*', $where)) {
					reset($rows);
					$conf = $GLOBALS["TSFE"]->tmpl->setup["tt_content."]["uploads."]["20."]["dam_mam."];
					$fileConf = $GLOBALS["TSFE"]->tmpl->setup["tt_content."]["uploads."]["20."]["linkProc."];
					$files = array();
					foreach ($rows as $row) {
						$fields = array();
						foreach ($row as $key => $value) {
							$fields["###" . strtoupper($key) . "###"] = $value;
						}

						$mediaLanguages = explode(",", $row["tx_dammam_language"]);
						$fields["###LANGUAGE_ICON###"] = '';
						foreach ($mediaLanguages as $mediaLanguage) {
							if(isset($languages[$mediaLanguage])){
								$language = $languages[$mediaLanguage];
							} else {
								$language = current($languages);
							}
							$fields["###LANGUAGE_ICONS###"].= '<img src="'.$this->pObj->cObj->typolink('',array(
								'returnLast'=>'url',
								'parameter'=> 'typo3/gfx/flags/' . $language['flag'] . '.gif'
							)).'" class="language-icon" />';
							foreach ($language as $key => $value) {
								$fields["###LANGUAGE_" . $key .  "_" . strtoupper($key) . "###"] = $value;
							}
						}
						$mediaLanguage = current($mediaLanguages);
						if(isset($languages[$mediaLanguage])){
							$language = $languages[$mediaLanguage];
						} else {
							$language = current($languages);
						}

						foreach ($language as $key => $value) {
							$fields["###LANGUAGE_" . strtoupper($key) . "###"] = $value;
						}
						$fields["###LANGUAGE_ICON###"] = $this->pObj->cObj->typolink('',array(
							'returnLast'=>'url',
							'parameter'=> 'typo3/gfx/flags/' . $language['flag'] . '.gif'
						));

						$url = $row["file_path"] . $row["file_name"];
						$fields["###URL###"] = $this->getFileUrl($url, $fileConf, array("dam" => $row["uid"]));
						$files[] = str_replace(array_keys($fields), array_values($fields), $conf["file"]);
					}

					$files = str_replace("|", implode("\n", $files), $conf["files"]);
					$fileLayout = str_replace("###RELATED_FILES###", $files, $fileLayout);
				}
			}
		}

		return $fileLayout;
	}
}

?>