<?php

require_once(t3lib_extMgm::extPath("dam") . 'lib/class.tx_dam_tcefunc.php');

class user_dammam_related_files extends tx_dam_tceFunc{
	var $cObj;
 
	public function user_related_files($PA, $fobj) {
		$result = '';
		if(strlen($PA['itemFormElValue']) > 0){
			$languages = array();
			foreach ($GLOBALS['TYPO3_DB']->exec_SELECTgetRows("*", 'sys_language', "1=1") as $lang) {
				$languages[$lang["uid"]] = $lang;
			}

			$where = array();
			$where['file_name'] = 'tx_dam.uid IN (' . $PA['itemFormElValue'] . ')';
			$where['enableFields'] = tx_dam_db::enableFields('tx_dam', '', $mode);
			$rows = tx_dam_db::getDataWhere('*', $where);
			$result = '<table style="width:100%;">';

			foreach ($rows as $row) {
				#t3lib_div::debug($row);
				#exit();
				$result.= '<tr>';

				$result.= '<td style="width:20px;">' . tx_dam::icon_getFileTypeImgTag($row) . '</td>';

				$result.= '<td>' . $row["title"] . '</td>';

				$result.= '<td>' . (isset($languages[$row["tx_dammam_language"]]) ? $languages[$row["tx_dammam_language"]]["title"] : 'Default') . '</td>';

				$path = PATH_site . $row["file_path"] . $row['file_name'];
				$url = 'http://crossmedia.wanzl.he-hosting.de/typo3/show_item.php?table='.($path).'&uid=';
				$result.= '<td><a href="' . $url . '"><img title="Display information" src="../../../../typo3/sysext/t3skin/icons/gfx/zoom2.gif" width="16" height="16" alt=""></a></td>';

				$result.= '</tr>';
			}
			$result.= '</table>';
		}
		return $result;
	}
}

?>