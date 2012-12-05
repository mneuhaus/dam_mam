<?php


class ux_tx_dam_actionCall extends tx_dam_actionCall{
	/**
	 * don't show Disabled actions by default
	 */
	function renderActionsHorizontal($checkValidStrict=false, $showDisabled=true) {
		return parent::renderActionsHorizontal($checkValidStrict, false);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dam_mam/class.ux_tx_dam_actioncall.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dam_mam/class.ux_tx_dam_actioncall.php']);
}

?>