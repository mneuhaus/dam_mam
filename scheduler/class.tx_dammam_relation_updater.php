<?php

class tx_dammam_relation_updater extends tx_scheduler_Task {

	public function execute() {
		$this->debugLevel = 1;
		$GLOBALS['DebugLevel'] = 1;
		$this->checkUnresolvedRelations();
		return TRUE;
	}

	public function checkUnresolvedRelations() {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_dammam_unresolved_relations', '1=1');
		$GLOBALS['DAM'] = array(
			'Relation Saving times' => array(),
			'Relation Saving median' => 0,
			'Saving Count for handleRealtedFiles=true' => array(),
			'Saving Count for handleRealtedFiles=false' => array()
		);
		foreach ($rows as $row) {
			if (empty($row['uuid_foreign'])) {
				continue;
			}

			if (stristr($row['uuid_foreign'], 'data_')) {
				$foreignObject = DamModel::getByMamUID($row['uuid_foreign']);
				$localObject = DamModel::getByMamUID($row['uuid_local']);
				if (!is_object($localObject)) {
					continue;
				}

				if (is_object($foreignObject) && intval($foreignObject->uid) > 0) {
					$foreignObject->logging = $this;
					$localObject->logging = $this;
					$localObject->addRelatedFile($foreignObject->uid);
					$localObject->save();
				} else {
					// $this->log('Couldnt resolve relation', array(
					// 	'row' => $row,
					// 	'foreignObject' => $foreignObject,
					// 	'localObject' => $localObject
					// ), 0);
				}
			} else {
				$localObject = DamModel::getByMamUID($row['uuid_local']);
				if ($row['uuid_foreign'] == $localObject->file_name) {
					continue;
				}

				if (is_object($localObject)) {
					$groupRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_dammam_unresolved_relations', 'uuid_foreign="' . $row['uuid_foreign'] . '"');
					foreach ($groupRows as $groupRow) {
						$foreignObject = DamModel::getByMamUID($groupRow['uuid_local']);
						if (is_object($foreignObject) && intval($foreignObject->uid) > 0 && $foreignObject->uid !== $localObject->uid) {
							$localObject->logging = $this;
							$localObject->addRelatedFile($foreignObject->uid);
							$localObject->save();
						}
					}
				}
			}
		}
	}
}
?>