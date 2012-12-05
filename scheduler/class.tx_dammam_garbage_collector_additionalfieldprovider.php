<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Steffen Müller <typo3@t3node.com>
*  (c) 2009 Francois Suter <francois@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Aditional fields provider class for usage with the 'Hide content' task
 *
 * @author		Francois Suter <francois@typo3.org>
 * @author		Steffen Müller <typo3@t3node.com>
 * @package		TYPO3
 * @subpackage		tx_smscheddemo
 *
 */
class tx_dammam_garbage_collector_AdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {
	/**
	 * @var array
	 */
	protected $fields = array(
		"header" => array(
			"label" => "<b>MAM Settings</b>",
			"type" => "blank",
			"default" => "",
			"style" => ""
		),
		"hotfolder" => array(
			"label" => "Hotfolder",
			"type" => "text",
			"default" => "Enter the path to the Hotfolder",
			"style" => "width:360px;"
		),
		"debuglevel" => array(
			"label" => "Debuglevel",
			"type" => "select",
			"default" => 2,
			"style" => "",
			"values" => array(
				0 => "Debugging",
				1 => "Info",
				2 => "Warning",
				3 => "Error"
			)
		),
	);
	/**
	 * Field generation.
	 * This method is used to define new fields for adding or editing a task
	 * In this case, it adds a page ID field
	 *
	 * @param	array			$taskInfo: reference to the array containing the info used in the add/edit form
	 * @param	object			$task: when editing, reference to the current task object. Null when adding.
	 * @param	tx_scheduler_Module	$parentObject: reference to the calling object (Scheduler's BE module)
	 * @return	array			Array containg all the information pertaining to the additional fields
	 *					The array is multidimensional, keyed to the task class name and each field's id
	 *					For each field it provides an associative sub-array with the following:
	 *						['code']		=> The HTML code for the field
	 *						['label']		=> The label of the field (possibly localized)
	 *						['cshKey']		=> The CSH key for the field
	 *						['cshLabel']		=> The code of the CSH label
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {
		$additionalFields = array();

		foreach ($this->fields as $key => $conf) {
			if (empty($taskInfo[$key])) {
				if ($parentObject->CMD == 'add') {
					$taskInfo[$key] = $conf["default"];
				} elseif ($parentObject->CMD == 'edit') {
					$taskInfo[$key] = $task->$key;
				} else {
					$taskInfo[$key] = '';
				}
			}
			$fieldID = 'task_' . $key;
			$name = 'tx_scheduler['.$key.']';
			switch ($conf["type"]) {
				case 'blank':
					$fieldCode = '';
					break;

				case 'select':
					$fieldCode = '<select name="' . $name . '" id="' . $fieldID . '" style="' . $conf["style"] . '">';
					foreach ($conf["values"] as $value => $label) {
						$selected = $taskInfo[$key] == $value ? "selected='selected'" : "";
						$fieldCode.= "<option value='" . $value . "' " . $selected . ">" . $label . "</option>";
					}
					$fieldCode.= '</select>';
					break;

				case 'text':
				default:
					$fieldCode = '<input type="text" name="' . $name . '" id="' . $fieldID . '" value="' . $taskInfo[$key] . '" style="'.$conf["style"].'"/>';
					break;
			}
			$additionalFields[$fieldID] = array(
				'code'	 => $fieldCode,
				'label'	=> $conf["label"],
				'cshKey'   => 'xMOD_tx_smscheddemo',
				'cshLabel' => $fieldID
			);
		}

		return $additionalFields;
	}

	/**
	 * Field validation.
	 * This method checks if page id given in the 'Hide content' specific task is int+
	 * If the task class is not relevant, the method is expected to return true
	 *
	 * @param	array			$submittedData: reference to the array containing the data submitted by the user
	 * @param	tx_scheduler_Module	$parentObject: reference to the calling object (Scheduler's BE module)
	 * @return	boolean			True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $parentObject) {

		foreach ($this->fields as $key => $conf) {
			$value = $submittedData[$key];
			$validator = "validate" . ucfirst($key);
			if(method_exists($this, $validator) && call_user_func(array($this, $validator), $value, $parentObject) == false)
				return false;
		}

		return true;

	}

	public function validateHotfolder($value, $parentObject){
		return $this->checkDir($value, "Hotfolder", $parentObject);
	}

	public function validateMediafolder($value, $parentObject){
		return $this->checkDir($value, "Media Folder", $parentObject);
	}

	public function checkDir($path, $name, $parentObject){

		// If value is not valid, report error with a flash message.
		if (empty($path)) {
			$parentObject->addMessage(
				"The path can't be empty!",
				t3lib_FlashMessage::ERROR
			);
			return false;
		} else if (!file_exists($path)) {
			$parentObject->addMessage(
				"The ".$name." does not exist!",
				t3lib_FlashMessage::ERROR
			);
			return false;
		} else if (!is_dir($path)) {
			$parentObject->addMessage(
				"The ".$name." is not a directory!",
				t3lib_FlashMessage::ERROR
			);
			return false;
		}

 		return true;
	}

	/**
	 * Store field.
	 * This method is used to save any additional input into the current task object
	 * if the task class matches
	 *
	 * @param	array			$submittedData: array containing the data submitted by the user
	 * @param	tx_scheduler_Task	$task: reference to the current task object
	 * @return	void
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		foreach ($this->fields as $key => $conf) {
			$task->$key = $submittedData[$key];
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dam_mam/classes/class.tx_dammam_sync_additionalfieldprovider.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dam_mam/classes/class.tx_dammam_sync_additionalfieldprovider.php']);
}

?>