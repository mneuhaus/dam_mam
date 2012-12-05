<?php

class ux_tx_dam_listrecords extends tx_dam_listrecords {
	/**
	 * Creates the column control panel for the header.
	 *
	 * @param 	string 	$field Column key
	 * @return	string		control panel (unless disabled)
	 */
	function getHeaderColumnControl($field) {
		return "";
	}
	
	/**
	 * Disable the Header control panel
	 *
	 * @return	string		control panel (unless disabled)
	 */
	function getHeaderControl() {
		return "";
	}
}
?>