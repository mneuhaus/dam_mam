<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2007 Rene Fritz (r.fritz@colorcube.de)
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   17: class tx_dam_show_item
 *   29:     function isValid($type, &$pObj)
 *   46:     function render($type, &$pObj)
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the script "update-class-index")
 *
 */
include_once(t3lib_extMgm::extPath("dam", "binding/be/class.tx_dam_show_item.php"));

class tx_dammam_show_item extends tx_dam_show_item {
	/**
	 * Rendering
	 *
	 * @param	string		Type: "file"
	 * @param	object		Parent object.
	 * @return	string		Rendered content
	 */
	function render($type, &$pObj)	{
		$content = '<link rel="stylesheet" type="text/css" href="../typo3conf/ext/dam_mam/css/metadata.css" media="all">';
		$content.= parent::render($type, $pObj);
		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dam_mam/dam/class.tx_dammam_show_item.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dam_mam/dam/class.tx_dammam_show_item.php']);
}
?>